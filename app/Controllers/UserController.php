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
 * Admin User Management Controller
 * Strict administrator-only user provisioning, status toggling, and password reset.
 */
class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * List all hospital personnel with search & role filters
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string)$request->query('search', ''),
            'role' => (string)$request->query('role', ''),
            'status' => $request->query('status', ''),
        ];

        $page = (int)$request->query('page', 1);
        $pagination = $this->userModel->getFilteredUsers($filters, $page, 15);

        return $this->render('users/index', [
            'users' => $pagination['data'],
            'pagination' => $pagination,
            'filters' => $filters,
            'pageTitle' => 'Personnel & Access Management',
        ]);
    }

    /**
     * Display User Creation Form
     */
    public function create(Request $request): Response
    {
        return $this->render('users/create', [
            'pageTitle' => 'Provision New Hospital Personnel',
        ]);
    }

    /**
     * Store New Hospital Personnel
     */
    public function store(Request $request): void
    {
        $data = [
            'employee_id' => trim((string)$request->input('employee_id', '')),
            'name' => trim((string)$request->input('name', '')),
            'email' => strtolower(trim((string)$request->input('email', ''))),
            'phone' => trim((string)$request->input('phone', '')),
            'role' => (string)$request->input('role', 'supervisor'),
            'designation' => trim((string)$request->input('designation', '')),
            'department' => trim((string)$request->input('department', '')),
            'password' => (string)$request->input('password', ''),
            'is_active' => (int)$request->input('is_active', 1),
        ];

        $errors = [];

        if ($data['employee_id'] === '') {
            $errors[] = 'Employee ID is required.';
        } elseif ($this->userModel->isEmployeeIdTaken($data['employee_id'])) {
            $errors[] = "Employee ID '{$data['employee_id']}' is already assigned to another employee.";
        }

        if ($data['name'] === '') {
            $errors[] = 'Full name is required.';
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif ($this->userModel->isEmailTaken($data['email'])) {
            $errors[] = "Email '{$data['email']}' is already registered in the system.";
        }

        if (!in_array($data['role'], ['admin', 'supervisor'], true)) {
            $errors[] = 'Invalid role specified.';
        }

        if (strlen($data['password']) < 8) {
            $errors[] = 'Initial password must be at least 8 characters long.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/users/create');
            return;
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $insertId = $this->userModel->insert([
            'employee_id' => $data['employee_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'password_hash' => $passwordHash,
            'role' => $data['role'],
            'designation' => $data['designation'] ?: null,
            'department' => $data['department'] ?: null,
            'is_active' => $data['is_active'],
        ]);

        AuditLog::record(
            Auth::id(),
            'USER_CREATED',
            'users',
            (int)$insertId,
            null,
            [
                'employee_id' => $data['employee_id'],
                'name' => $data['name'],
                'role' => $data['role'],
                'department' => $data['department'],
            ],
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->flash('success', "Personnel '{$data['name']}' ({$data['employee_id']}) provisioned successfully.");
        $this->redirect('/users');
    }

    /**
     * Display User Edit Form
     */
    public function edit(Request $request, string $id): Response
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->flash('error', 'The requested personnel record was not found.');
            $this->redirect('/users');
        }

        return $this->render('users/edit', [
            'user' => $user,
            'pageTitle' => "Edit Personnel: {$user['name']}",
        ]);
    }

    /**
     * Update Existing Personnel Record
     */
    public function update(Request $request, string $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/users');
            return;
        }

        $userId = (int)$user['id'];

        $name = trim((string)$request->input('name', ''));
        $email = strtolower(trim((string)$request->input('email', '')));
        $phone = trim((string)$request->input('phone', ''));
        $role = (string)$request->input('role', $user['role']);
        $designation = trim((string)$request->input('designation', ''));
        $department = trim((string)$request->input('department', ''));

        $errors = [];

        if ($name === '') {
            $errors[] = 'Full name cannot be blank.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif ($this->userModel->isEmailTaken($email, $userId)) {
            $errors[] = "Email '{$email}' is already in use by another account.";
        }

        if (!in_array($role, ['admin', 'supervisor'], true)) {
            $errors[] = 'Invalid role specified.';
        }

        // Prevent admin from removing their own admin role
        if ($userId === Auth::id() && $role !== 'admin') {
            $errors[] = 'You cannot revoke your own Administrator role.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect("/users/{$userId}/edit");
            return;
        }

        $updateData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'role' => $role,
            'designation' => $designation ?: null,
            'department' => $department ?: null,
        ];

        $this->userModel->update($userId, $updateData);

        AuditLog::record(
            Auth::id(),
            'USER_UPDATED',
            'users',
            $userId,
            [
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email'],
            ],
            $updateData,
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->flash('success', "Personnel profile for '{$name}' updated successfully.");
        $this->redirect('/users');
    }

    /**
     * Toggle User Activation Status
     */
    public function toggleStatus(Request $request, string $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/users');
            return;
        }

        $userId = (int)$user['id'];

        // Guard: Prevent admin from deactivating themselves
        if ($userId === Auth::id()) {
            $this->flash('error', 'Action prohibited: You cannot deactivate your own active session account.');
            $this->redirect('/users');
            return;
        }

        $newStatus = ((int)$user['is_active'] === 1) ? 0 : 1;
        $statusLabel = $newStatus === 1 ? 'activated' : 'deactivated';

        $this->userModel->update($userId, ['is_active' => $newStatus]);

        AuditLog::record(
            Auth::id(),
            $newStatus === 1 ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
            'users',
            $userId,
            ['is_active' => $user['is_active']],
            ['is_active' => $newStatus],
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->flash('success', "Account for '{$user['name']}' has been {$statusLabel}.");
        $this->redirect('/users');
    }

    /**
     * Admin Workflow: Reset Employee Password
     */
    public function resetPassword(Request $request, string $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/users');
            return;
        }

        $userId = (int)$user['id'];
        $newPassword = (string)$request->input('new_password', '');

        if (strlen($newPassword) < 8) {
            $this->flash('error', 'The new temporary password must be at least 8 characters long.');
            $this->redirect('/users');
            return;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userModel->updatePassword($userId, $hash);
        $this->userModel->resetLockout($userId);

        AuditLog::record(
            Auth::id(),
            'ADMIN_RESET_PASSWORD',
            'users',
            $userId,
            null,
            ['reset_by_admin_id' => Auth::id()],
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $this->flash('success', "Password for '{$user['name']}' has been reset and account lockouts cleared.");
        $this->redirect('/users');
    }
}
