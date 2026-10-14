<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\User;

/**
 * Authentication Controller
 * Manages login, logout, self profile, and password update workflows.
 */
class AuthController extends Controller
{
    /**
     * Display Login Form
     */
    public function showLogin(Request $request): Response
    {
        return $this->render('auth/login', [
            'pageTitle' => 'Sign In - Hospital Inspection & Facility Portal',
        ], 'layouts/main');
    }

    /**
     * Process Login Submission
     */
    public function login(Request $request): void
    {
        $identity = (string)$request->input('identity', '');
        $password = (string)$request->input('password', '');

        $result = Auth::attempt(
            $identity,
            $password,
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        if (!$result['success']) {
            $this->flash('error', $result['error']);
            $this->redirect('/login');
            return;
        }

        $user = $result['user'];
        $this->flash('success', "Welcome back, {$user['name']}! Authenticated as " . ucfirst($user['role']) . '.');

        if ($user['role'] === 'supervisor') {
            $this->redirect('/supervisor/overview');
        } else {
            $this->redirect('/dashboard');
        }
    }

    /**
     * Process Logout
     */
    public function logout(Request $request): void
    {
        Auth::logout();
        $this->flash('success', 'You have been securely signed out of the hospital portal.');
        $this->redirect('/login');
    }

    /**
     * Display Current User Profile
     */
    public function profile(Request $request): Response
    {
        $user = Auth::user();
        $userModel = new User();

        $assignments = [];
        $activeShift = null;

        if ($user['role'] === 'supervisor') {
            $assignments = $userModel->getSupervisorAssignments((int)$user['id']);
            $activeShift = $userModel->getActiveShift((int)$user['id']);
        }

        return $this->render('auth/profile', [
            'user' => $user,
            'assignments' => $assignments,
            'activeShift' => $activeShift,
            'pageTitle' => 'My Facility Profile',
        ]);
    }

    /**
     * Display Change Password Form
     */
    public function showChangePassword(Request $request): Response
    {
        return $this->render('auth/change_password', [
            'pageTitle' => 'Change Account Password',
        ]);
    }

    /**
     * Process Password Change
     */
    public function changePassword(Request $request): void
    {
        $currentPassword = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');
        $confirmPassword = (string)$request->input('confirm_password', '');

        $errors = [];

        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        }

        if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'New password must contain uppercase letters, lowercase letters, and numbers.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password confirmation does not match.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/change-password');
            return;
        }

        $user = Auth::user();

        // Verify current password against database hash
        if (!password_verify($currentPassword, $user['password_hash'])) {
            AuditLog::record(
                (int)$user['id'],
                'PASSWORD_CHANGE_FAILED_WRONG_CURRENT',
                'users',
                (int)$user['id'],
                null,
                ['error' => 'Invalid current password supplied'],
                $request->getIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $this->flash('error', 'The current password you provided is incorrect.');
            $this->redirect('/change-password');
            return;
        }

        // Generate strong bcrypt hash (cost 12)
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $userModel = new User();
        $userModel->updatePassword((int)$user['id'], $newHash);

        AuditLog::record(
            (int)$user['id'],
            'PASSWORD_CHANGE_SUCCESS',
            'users',
            (int)$user['id'],
            null,
            ['changed' => true],
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->flash('success', 'Your password has been successfully updated.');
        $this->redirect('/profile');
    }

    /**
     * Display Forgot Password Request Form
     */
    public function showForgotPassword(Request $request): Response
    {
        return $this->render('auth/forgot_password', [
            'pageTitle' => 'Reset Password - Sheth L.G. General Hospital',
        ], 'layouts/main');
    }

    /**
     * Process Forgot Password Request (Rate limited, anti-enumeration)
     */
    public function sendResetLink(Request $request): void
    {
        $identity = (string)$request->input('identity', '');
        $resetService = new \App\Services\PasswordResetService();
        $result = $resetService->requestReset($identity, $request->getIp());

        if (!$result['success']) {
            $this->flash('error', $result['error']);
            $this->redirect('/forgot-password');
            return;
        }

        // In development/demo, expose simulation token in flash or log
        if (!empty($result['token'])) {
            $resetUrl = url('/reset-password?token=' . urlencode($result['token']));
            $this->flash('info', "Clinical Demo: Single-use reset token generated. <a href='{$resetUrl}' class='alert-link fw-bold'>Click here to set new password</a> (Valid 15 minutes).");
        } else {
            $this->flash('success', $result['message']);
        }

        $this->redirect('/forgot-password');
    }

    /**
     * Display Set New Password Form (Validates token)
     */
    public function showResetPassword(Request $request): Response
    {
        $rawToken = (string)$request->query('token', '');
        $resetService = new \App\Services\PasswordResetService();
        $tokenRecord = $resetService->validateToken($rawToken);

        if (!$tokenRecord) {
            $this->flash('error', 'The password reset link is invalid, expired, or has already been used.');
            $this->redirect('/forgot-password');
        }

        return $this->render('auth/reset_password', [
            'pageTitle'   => 'Set New Password - Sheth L.G. General Hospital',
            'token'       => $rawToken,
            'tokenRecord' => $tokenRecord,
        ], 'layouts/main');
    }

    /**
     * Process New Password Submission (Single-use invalidation)
     */
    public function resetPassword(Request $request): void
    {
        $rawToken = (string)$request->input('token', '');
        $newPassword = (string)$request->input('password', '');
        $confirmPassword = (string)$request->input('password_confirmation', '');

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'New password and confirmation do not match.');
            $this->redirect('/reset-password?token=' . urlencode($rawToken));
            return;
        }

        $resetService = new \App\Services\PasswordResetService();
        $result = $resetService->completeReset($rawToken, $newPassword, $request->getIp());

        if (!$result['success']) {
            $this->flash('error', $result['error']);
            $this->redirect('/reset-password?token=' . urlencode($rawToken));
            return;
        }

        $this->flash('success', $result['message']);
        $this->redirect('/login');
    }
}
