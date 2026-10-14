<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateVersion;
use App\Services\ChecklistVersionService;

/**
 * ChecklistController
 * Administers hospital checklist templates, immutable versions, items, and facility scoping.
 */
class ChecklistController extends Controller
{
    private ChecklistTemplate $templateModel;
    private ChecklistTemplateVersion $versionModel;
    private ChecklistItem $itemModel;
    private Building $buildingModel;

    public function __construct()
    {
        $this->templateModel = new ChecklistTemplate();
        $this->versionModel = new ChecklistTemplateVersion();
        $this->itemModel = new ChecklistItem();
        $this->buildingModel = new Building();
    }

    /**
     * List all checklist templates
     */
    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->query('category', ''),
            'status'   => $request->query('status', ''),
            'search'   => $request->query('search', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->templateModel->getAllWithDetails($filters, $page, 15);
        $buildings = $this->buildingModel->getAllWithStats('active');

        return $this->render('checklists/index', [
            'templates'  => $pagination['data'],
            'pagination' => $pagination,
            'filters'    => $filters,
            'buildings'  => $buildings,
            'categories' => ChecklistTemplate::CATEGORIES,
            'pageTitle'  => 'Inspection Checklist Templates',
        ]);
    }

    /**
     * Store new checklist template
     */
    public function store(Request $request): void
    {
        $post = $request->post();
        $code = strtoupper(trim((string)($post['template_code'] ?? '')));
        $name = trim((string)($post['template_name'] ?? ''));
        $category = (string)($post['category'] ?? 'hygiene');
        $desc = trim((string)($post['description'] ?? ''));
        $isMandatory = isset($post['is_mandatory']) ? 1 : 0;

        $buildingId = !empty($post['target_building_id']) ? (int)$post['target_building_id'] : null;
        $floorId = !empty($post['target_floor_id']) ? (int)$post['target_floor_id'] : null;
        $wardId = !empty($post['target_ward_id']) ? (int)$post['target_ward_id'] : null;
        $locationId = !empty($post['target_location_id']) ? (int)$post['target_location_id'] : null;

        if (empty($code) || empty($name)) {
            Session::setFlash('error', 'Template code and name are required.');
            $this->redirect('/checklists');
            return;
        }

        if ($this->templateModel->isCodeTaken($code)) {
            Session::setFlash('error', "Template code '{$code}' is already registered.");
            $this->redirect('/checklists');
            return;
        }

        $templateId = $this->templateModel->create([
            'template_code'      => $code,
            'template_name'      => $name,
            'category'           => $category,
            'description'        => !empty($desc) ? $desc : null,
            'target_building_id' => $buildingId,
            'target_floor_id'    => $floorId,
            'target_ward_id'     => $wardId,
            'target_location_id' => $locationId,
            'is_mandatory'       => $isMandatory,
            'status'             => 'draft',
            'created_by'         => (int)Auth::id(),
        ]);

        // Create initial Version 1 snapshot
        ChecklistVersionService::createInitialVersion($templateId, (int)Auth::id());

        AuditLog::log(
            AuditLog::EVENT_FACILITY_CREATED,
            "Created checklist template '{$name}' ({$code}) with initial Version 1",
            Auth::id(),
            ['template_id' => $templateId, 'code' => $code]
        );

        Session::setFlash('success', "Template '{$name}' created successfully. Now add checklist inspection items.");
        $this->redirect("/checklists/{$templateId}/edit");
    }

    /**
     * Edit template and manage active checklist items
     */
    public function edit(Request $request): Response
    {
        $id = (int)$request->param('id');
        $template = $this->templateModel->find($id);

        if (!$template || $template['deleted_at'] !== null) {
            Session::setFlash('error', 'Checklist template not found.');
            $this->redirect('/checklists');
        }

        $currentVersion = ChecklistVersionService::getCurrentVersion($id);
        $items = $currentVersion ? $this->versionModel->getItems((int)$currentVersion['id']) : [];
        $isVersionUsed = $currentVersion ? ChecklistVersionService::hasVersionBeenUsed((int)$currentVersion['id']) : false;
        $versionHistory = ChecklistVersionService::getVersionHistory($id);

        return $this->render('checklists/edit', [
            'template'       => $template,
            'currentVersion' => $currentVersion,
            'items'          => $items,
            'isVersionUsed'  => $isVersionUsed,
            'versionHistory' => $versionHistory,
            'categories'     => ChecklistTemplate::CATEGORIES,
            'itemTypes'      => ChecklistItem::ITEM_TYPES,
            'severities'     => ChecklistItem::SEVERITIES,
            'pageTitle'      => "Edit Checklist: {$template['template_name']}",
        ]);
    }

    /**
     * Add item to current template version
     */
    public function addItem(Request $request): void
    {
        $templateId = (int)$request->param('id');
        $template = $this->templateModel->find($templateId);

        if (!$template) {
            Session::setFlash('error', 'Template not found.');
            $this->redirect('/checklists');
            return;
        }

        $currentVersion = ChecklistVersionService::getCurrentVersion($templateId);
        if (!$currentVersion) {
            Session::setFlash('error', 'No active version snapshot found.');
            $this->redirect("/checklists/{$templateId}/edit");
            return;
        }

        // Immutability defense: If version has already been used in completed inspections, block in-place edit!
        if (ChecklistVersionService::hasVersionBeenUsed((int)$currentVersion['id'])) {
            Session::setFlash('error', "Version {$currentVersion['version_number']} has already been used in completed inspections. You must branch a new version before adding or modifying items to preserve historical audit integrity.");
            $this->redirect("/checklists/{$templateId}/edit");
            return;
        }

        $post = $request->post();
        $label = trim((string)($post['item_label'] ?? ''));
        $type = (string)($post['item_type'] ?? 'pass_fail');
        $desc = trim((string)($post['description'] ?? ''));
        $isRequired = isset($post['is_required']) ? 1 : 0;
        $severity = (string)($post['severity'] ?? 'medium');
        $requiresEvidence = isset($post['requires_evidence']) ? 1 : 0;
        $isCritical = ($severity === 'critical') ? 1 : (isset($post['is_critical']) ? 1 : 0);

        if (empty($label)) {
            Session::setFlash('error', 'Checklist question/label is required.');
            $this->redirect("/checklists/{$templateId}/edit");
            return;
        }

        $this->itemModel->create([
            'template_version_id' => (int)$currentVersion['id'],
            'item_label'          => $label,
            'description'         => !empty($desc) ? $desc : null,
            'item_type'           => $type,
            'is_required'         => $isRequired,
            'severity'            => $severity,
            'is_critical'         => $isCritical,
            'requires_evidence'   => $requiresEvidence,
            'display_order'       => count($this->versionModel->getItems((int)$currentVersion['id'])) + 1,
        ]);

        Session::setFlash('success', 'Checklist item added successfully.');
        $this->redirect("/checklists/{$templateId}/edit");
    }

    /**
     * Delete item from current version (only permitted if version has not been used)
     */
    public function deleteItem(Request $request): void
    {
        $templateId = (int)$request->param('id');
        $itemId = (int)$request->param('itemId');

        $currentVersion = ChecklistVersionService::getCurrentVersion($templateId);
        if (!$currentVersion) {
            Session::setFlash('error', 'Version not found.');
            $this->redirect("/checklists/{$templateId}/edit");
            return;
        }

        if (ChecklistVersionService::hasVersionBeenUsed((int)$currentVersion['id'])) {
            Session::setFlash('error', 'Cannot delete item from an active version already referenced by inspections.');
            $this->redirect("/checklists/{$templateId}/edit");
            return;
        }

        $this->itemModel->delete($itemId);
        Session::setFlash('success', 'Checklist item removed.');
        $this->redirect("/checklists/{$templateId}/edit");
    }

    /**
     * Branch/clone a new template version when updating a used template
     */
    public function branchVersion(Request $request): void
    {
        $id = (int)$request->param('id');
        $changelog = trim((string)($request->post('changelog') ?? 'Branched version for updated protocol'));

        try {
            $newVersionId = ChecklistVersionService::branchNewVersion($id, (int)Auth::id(), $changelog);
            Session::setFlash('success', 'New version snapshot branched successfully. Prior inspection records remain linked to their original historical snapshots.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to branch new version: ' . $e->getMessage());
        }

        $this->redirect("/checklists/{$id}/edit");
    }

    /**
     * Publish or toggle status of checklist template
     */
    public function toggleStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $template = $this->templateModel->find($id);

        if (!$template) {
            Session::setFlash('error', 'Template not found.');
            $this->redirect('/checklists');
            return;
        }

        $currentStatus = $template['status'];
        $newStatus = ($currentStatus === 'published') ? 'draft' : 'published';

        $this->templateModel->update($id, ['status' => $newStatus]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Changed checklist template '{$template['template_name']}' status to {$newStatus}",
            Auth::id(),
            ['template_id' => $id, 'new_status' => $newStatus]
        );

        Session::setFlash('success', "Template status changed to {$newStatus}.");
        $this->redirect('/checklists');
    }
}
