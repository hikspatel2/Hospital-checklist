<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\SlaPolicy;

/**
 * SLA Policy Controller (Admin Only)
 * Manages category and priority SLA response and resolution targets.
 */
class SlaPolicyController extends Controller
{
    private SlaPolicy $slaModel;

    public function __construct()
    {
        $this->slaModel = new SlaPolicy();
    }

    /**
     * SLA Policies Table
     */
    public function index(Request $request): Response
    {
        $policies = $this->slaModel->getActivePolicies();

        return $this->view('sla/index', [
            'pageTitle'  => 'SLA Policies Configuration',
            'policies'   => $policies,
            'categories' => Complaint::CATEGORIES,
            'priorities' => Complaint::PRIORITIES,
        ]);
    }

    /**
     * Store or update an SLA policy
     */
    public function store(Request $request): void
    {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized access.');
            Response::redirect('/sla-policies');
            return;
        }

        $policyName = trim((string)$request->post('policy_name', ''));
        $category = trim((string)$request->post('category', 'all'));
        $priority = trim((string)$request->post('priority', 'medium'));
        $responseHours = (int)$request->post('response_time_hours', 4);
        $resolutionHours = (int)$request->post('resolution_time_hours', 24);
        $escalationEmail = trim((string)$request->post('escalation_email', ''));

        if ($policyName === '' || $responseHours <= 0 || $resolutionHours <= 0) {
            Session::flash('error', 'Please provide a valid policy name, and positive hours for response and resolution targets.');
            Response::redirect('/sla-policies');
            return;
        }

        if ($resolutionHours < $responseHours) {
            Session::flash('error', 'Resolution time target cannot be shorter than response time target.');
            Response::redirect('/sla-policies');
            return;
        }

        try {
            $policyId = $this->slaModel->savePolicy([
                'id'                    => $request->post('id'),
                'policy_name'           => $policyName,
                'category'              => $category,
                'priority'              => $priority,
                'response_time_hours'   => $responseHours,
                'resolution_time_hours' => $resolutionHours,
                'escalation_email'      => $escalationEmail ?: null,
                'is_active'             => 1,
            ]);

            AuditLog::log('sla_policy.save', 'sla_policies', $policyId, null, [
                'policy_name'           => $policyName,
                'category'              => $category,
                'priority'              => $priority,
                'response_time_hours'   => $responseHours,
                'resolution_time_hours' => $resolutionHours,
            ]);

            Session::flash('success', 'SLA Policy saved successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to save SLA policy: ' . $e->getMessage());
        }

        Response::redirect('/sla-policies');
    }
}
