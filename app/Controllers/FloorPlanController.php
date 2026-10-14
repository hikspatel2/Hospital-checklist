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
use App\Models\Floor;
use App\Models\FloorPlanArea;
use App\Models\Ward;

/**
 * FloorPlanController
 * Interactive SVG floor plan engine, real-time inspection status overlay,
 * area zoning, and protected floor plan file uploads.
 */
class FloorPlanController extends Controller
{
    private Building $buildingModel;
    private Floor $floorModel;
    private Ward $wardModel;
    private FloorPlanArea $areaModel;

    public function __construct()
    {
        $this->buildingModel = new Building();
        $this->floorModel = new Floor();
        $this->wardModel = new Ward();
        $this->areaModel = new FloorPlanArea();
    }

    /**
     * Interactive Floor Plan Viewer Page
     */
    public function interactive(Request $request): Response
    {
        $buildings = $this->buildingModel->getAllWithStats('active');
        $selectedBuildingId = (int)$request->query('building_id', $buildings[0]['id'] ?? 1);

        $floors = $this->floorModel->getByBuilding($selectedBuildingId, true);
        $selectedFloorId = (int)$request->query('floor_id', $floors[0]['id'] ?? 1);

        $activeFloor = $this->floorModel->findWithBuilding($selectedFloorId);
        $areas = $this->areaModel->getByFloor($selectedFloorId);
        $summary = $this->areaModel->getSummaryByFloor($selectedFloorId);
        $wards = $this->wardModel->getByFloor($selectedFloorId, true);

        return $this->render('facility/floor_plans/interactive', [
            'buildings'          => $buildings,
            'selectedBuildingId' => $selectedBuildingId,
            'floors'             => $floors,
            'selectedFloorId'    => $selectedFloorId,
            'activeFloor'        => $activeFloor,
            'areas'              => $areas,
            'summary'            => $summary,
            'wards'              => $wards,
            'pageTitle'          => 'Interactive Floor Plan & Area Inspections',
        ]);
    }

    /**
     * AJAX endpoint: Get area zones and inspection status for a floor
     */
    public function apiFloorAreas(Request $request): Response
    {
        $floorId = (int)$request->param('id');
        $floor = $this->floorModel->find($floorId);

        if (!$floor) {
            return $this->json(['success' => false, 'error' => 'Floor not found'], 404);
        }

        $areas = $this->areaModel->getByFloor($floorId);
        $summary = $this->areaModel->getSummaryByFloor($floorId);

        return $this->json([
            'success' => true,
            'floor'   => $floor,
            'areas'   => $areas,
            'summary' => $summary,
        ]);
    }

    /**
     * Update inspection status for an area
     */
    public function apiUpdateAreaStatus(Request $request): Response
    {
        $id = (int)$request->param('id');
        $area = $this->areaModel->find($id);

        if (!$area) {
            return $this->json(['success' => false, 'error' => 'Area not found'], 404);
        }

        $post = $request->post();
        $status = (string)($post['status'] ?? '');
        $color = !empty($post['color']) ? (string)$post['color'] : null;
        $notes = !empty($post['notes']) ? trim((string)$post['notes']) : null;

        try {
            $this->areaModel->updateInspectionStatus($id, $status, $color);
            if ($notes !== null) {
                $this->areaModel->update($id, ['notes' => $notes]);
            }

            AuditLog::log(
                AuditLog::EVENT_INSPECTION_COMPLETED,
                "Updated floor plan area #{$id} status to {$status}",
                Auth::id(),
                ['area_id' => $id, 'status' => $status]
            );

            return $this->json([
                'success' => true,
                'message' => "Area status updated to {$status}",
                'status'  => $status,
                'color'   => $color ?? (FloorPlanArea::STATUS_COLORS[$status] ?? '#0f2c59')
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Link/create floor plan area zone to a ward/SVG element
     */
    public function apiSaveArea(Request $request): Response
    {
        $post = $request->post();
        $floorId = (int)($post['floor_id'] ?? 0);
        $wardId = !empty($post['ward_id']) ? (int)$post['ward_id'] : null;
        $areaLabel = trim((string)($post['area_label'] ?? ''));
        $svgElementId = trim((string)($post['svg_element_id'] ?? ''));
        $shapeType = (string)($post['shape_type'] ?? 'rect');
        $coordinates = (string)($post['coordinates_json'] ?? '[]');
        $status = (string)($post['inspection_status'] ?? 'pending');
        $notes = trim((string)($post['notes'] ?? ''));

        if ($floorId <= 0 || empty($areaLabel)) {
            return $this->json(['success' => false, 'error' => 'Floor and area label are required.'], 400);
        }

        // Validate hierarchy: if ward is supplied, it must belong to floor
        if ($wardId !== null) {
            $ward = $this->wardModel->find($wardId);
            if (!$ward || (int)$ward['floor_id'] !== $floorId) {
                return $this->json(['success' => false, 'error' => 'Selected ward does not belong to this floor.'], 400);
            }
        }

        $areaId = (int)($post['area_id'] ?? 0);
        $color = FloorPlanArea::STATUS_COLORS[$status] ?? '#0f2c59';

        if ($areaId > 0) {
            $this->areaModel->update($areaId, [
                'ward_id'          => $wardId,
                'area_label'       => $areaLabel,
                'svg_element_id'   => $svgElementId,
                'shape_type'       => $shapeType,
                'coordinates_json' => $coordinates,
                'color_code'       => $color,
                'inspection_status'=> $status,
                'notes'            => $notes,
            ]);
            $action = 'updated';
        } else {
            $areaId = $this->areaModel->create([
                'floor_id'         => $floorId,
                'ward_id'          => $wardId,
                'area_label'       => $areaLabel,
                'svg_element_id'   => $svgElementId,
                'shape_type'       => $shapeType,
                'coordinates_json' => $coordinates,
                'color_code'       => $color,
                'inspection_status'=> $status,
                'notes'            => $notes,
            ]);
            $action = 'created';
        }

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "{$action} floor plan area zone '{$areaLabel}' on floor #{$floorId}",
            Auth::id(),
            ['area_id' => $areaId, 'floor_id' => $floorId]
        );

        return $this->json([
            'success' => true,
            'message' => "Area zone {$action} successfully.",
            'area_id' => $areaId
        ]);
    }

    /**
     * Protected floor plan upload with strict MIME validation,
     * SVG sanitation, and random hash filename storage.
     */
    public function uploadPlan(Request $request): void
    {
        $floorId = (int)$request->param('id');
        $floor = $this->floorModel->find($floorId);

        if (!$floor) {
            Session::setFlash('error', 'Floor not found.');
            $this->redirect('/admin/buildings');
            return;
        }

        if (empty($_FILES['floor_plan_file']) || $_FILES['floor_plan_file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Please select a valid floor plan file to upload.');
            $this->redirect('/admin/floors/' . $floorId);
            return;
        }

        $file = $_FILES['floor_plan_file'];
        $maxBytes = 5 * 1024 * 1024; // 5 MB

        if ($file['size'] > $maxBytes) {
            Session::setFlash('error', 'File size exceeds 5MB limit.');
            $this->redirect('/admin/floors/' . $floorId);
            return;
        }

        // Verify real MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/svg+xml' => 'svg',
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
        ];

        // Also check if SVG was detected as text/plain or text/xml
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($originalExt === 'svg' && ($mime === 'text/plain' || $mime === 'text/xml')) {
            $mime = 'image/svg+xml';
        }

        if (!array_key_exists($mime, $allowedMimes)) {
            Session::setFlash('error', 'Invalid file type. Only clean SVG vector blueprints or high-res PNG/JPG images are accepted.');
            $this->redirect('/admin/floors/' . $floorId);
            return;
        }

        $ext = $allowedMimes[$mime];

        // Strict SVG Sanitation to block XSS and malicious scripts
        if ($ext === 'svg') {
            $svgContent = file_get_contents($file['tmp_name']);
            if ($svgContent === false) {
                Session::setFlash('error', 'Unable to read uploaded file.');
                $this->redirect('/admin/floors/' . $floorId);
                return;
            }

            $dangerousPatterns = [
                '/<script\b[^>]*>(.*?)<\/script>/is',
                '/<!ENTITY/i',
                '/SYSTEM\s+["\']/i',
                '/onload\s*=/i',
                '/onerror\s*=/i',
                '/onclick\s*=/i',
                '/onmouseover\s*=/i',
                '/javascript\s*:/i',
                '/<foreignObject\b/i',
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $svgContent)) {
                    AuditLog::log(
                        AuditLog::EVENT_SUSPICIOUS_ACTIVITY,
                        "Malicious payload blocked during floor plan upload on floor #{$floorId}",
                        Auth::id(),
                        ['pattern' => $pattern, 'mime' => $mime]
                    );
                    Session::setFlash('error', 'Security Alert: Malicious script tags or unsafe entities detected in the uploaded SVG file.');
                    $this->redirect('/admin/floors/' . $floorId);
                    return;
                }
            }
        }

        // Destination directory: public/assets/uploads/floor_plans/
        $uploadDir = dirname(__DIR__, 2) . '/public/assets/uploads/floor_plans';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Random hash filename
        $safeFilename = 'plan_fl' . $floorId . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $destination = $uploadDir . '/' . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Session::setFlash('error', 'Failed to store uploaded floor plan on server.');
            $this->redirect('/admin/floors/' . $floorId);
            return;
        }

        $publicPath = '/assets/uploads/floor_plans/' . $safeFilename;
        $this->floorModel->update($floorId, ['plan_image_path' => $publicPath]);

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Uploaded new floor plan blueprint for floor #{$floorId}",
            Auth::id(),
            ['floor_id' => $floorId, 'path' => $publicPath]
        );

        Session::setFlash('success', 'Floor plan blueprint uploaded and secured successfully.');
        $this->redirect('/admin/floors/' . $floorId);
    }
}
