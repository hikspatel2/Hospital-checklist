<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\InspectionLocation;
use App\Models\QrToken;
use App\Models\Ward;
use App\Services\QrCodeService;

/**
 * QrController
 * Administers floor, ward, and checkpoint QR tokens, revocation, rotation, and SVG badge generation.
 */
class QrController extends Controller
{
    private QrToken $tokenModel;
    private Ward $wardModel;
    private InspectionLocation $locationModel;

    public function __construct()
    {
        $this->tokenModel = new QrToken();
        $this->wardModel = new Ward();
        $this->locationModel = new InspectionLocation();
    }

    /**
     * QR Tokens Console: lists active & revoked tokens across wards and checkpoints
     */
    public function index(Request $request): Response
    {
        $filters = [
            'token_type'  => $request->query('token_type', ''),
            'entity_type' => $request->query('entity_type', ''),
            'is_revoked'  => $request->query('is_revoked', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->tokenModel->getAllWithDetails($filters, $page, 15);
        $wards = $this->wardModel->getAllWithDetails(['status' => 'active'], 1, 100)['data'] ?? [];

        return $this->render('qr/index', [
            'tokens'     => $pagination['data'],
            'pagination' => $pagination,
            'filters'    => $filters,
            'wards'      => $wards,
            'pageTitle'  => 'QR Code Tokens & Checkpoint Administration',
        ]);
    }

    /**
     * Provision or regenerate an opaque QR token for a ward or checkpoint
     */
    public function generate(Request $request): void
    {
        $post = $request->post();
        $entityType = (string)($post['entity_type'] ?? 'ward');
        $entityId = (int)($post['entity_id'] ?? 0);
        $tokenType = (string)($post['token_type'] ?? 'ward_checkin');

        if ($entityId <= 0) {
            Session::setFlash('error', 'Valid target entity is required.');
            $this->redirect('/qr-codes');
            return;
        }

        $res = QrCodeService::createToken(
            $entityType,
            $entityId,
            $tokenType,
            (int)Auth::id()
        );

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Regenerated opaque QR token for {$entityType} #{$entityId}",
            Auth::id(),
            ['entity_type' => $entityType, 'entity_id' => $entityId, 'token_id' => $res['token_id']]
        );

        Session::setFlash('success', "New opaque QR token generated successfully. Previous tokens for this target have been revoked.");
        $this->redirect('/qr-codes');
    }

    /**
     * Revoke a specific QR token
     */
    public function revoke(Request $request): void
    {
        $id = (int)$request->param('id');
        $token = $this->tokenModel->find($id);

        if (!$token) {
            Session::setFlash('error', 'Token not found.');
            $this->redirect('/qr-codes');
            return;
        }

        QrCodeService::revokeToken($id, (int)Auth::id());

        AuditLog::log(
            AuditLog::EVENT_FACILITY_UPDATED,
            "Revoked QR token #{$id}",
            Auth::id(),
            ['token_id' => $id]
        );

        Session::setFlash('success', 'QR token revoked successfully. Any check-ins using this token will be rejected.');
        $this->redirect('/qr-codes');
    }

    /**
     * Render SVG QR badge directly
     */
    public function renderSvg(Request $request): Response
    {
        $raw = (string)$request->query('token', '');
        if (empty($raw)) {
            return new Response('Token parameter missing', 400);
        }

        $size = max(100, min(600, (int)$request->query('size', 240)));
        $svg = QrCodeService::renderSvg($raw, $size, 'Sheth L.G. General Hospital Checkpoint');

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    /**
     * AJAX endpoint to verify a scanned token
     */
    public function apiVerify(Request $request): Response
    {
        $token = trim((string)$request->query('token', ''));
        if (empty($token)) {
            return $this->json(['valid' => false, 'error' => 'Token parameter required.'], 400);
        }

        $result = QrCodeService::verifyToken($token);
        return $this->json($result);
    }
}
