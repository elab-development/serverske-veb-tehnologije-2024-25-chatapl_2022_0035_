<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InputValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define validation rules based on route
        $rules = $this->getValidationRules($request);
        
        if (!empty($rules)) {
            try {
                $validator = Validator::make($request->all(), $rules, $this->getCustomMessages());
                
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors(),
                        'error_code' => 'VALIDATION_ERROR'
                    ], 422);
                }
                
                // Sanitize validated data
                $sanitizedData = $this->sanitizeValidatedData($validator->validated());
                $request->replace($sanitizedData);
                
            } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation exception',
                    'errors' => $e->errors(),
                    'error_code' => 'VALIDATION_EXCEPTION'
                ], 422);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get validation rules based on request route and method.
     */
    protected function getValidationRules(Request $request): array
    {
        $route = $request->route();
        $method = $request->method();
        
        if (!$route) {
            return [];
        }
        
        $routeName = $route->getName();
        $routePath = $route->uri();
        
        // Authentication routes
        if (str_contains($routePath, 'login')) {
            return [
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8|max:255',
            ];
        }
        
        if (str_contains($routePath, 'register')) {
            return [
                'name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s]+$/',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'password_confirmation' => 'required|same:password',
            ];
        }
        
        // Room routes
        if (str_contains($routePath, 'rooms') && $method === 'POST') {
            return [
                'name' => 'required|string|min:3|max:100|regex:/^[a-zA-Z0-9\s\-_]+$/',
                'description' => 'nullable|string|max:500',
                'is_private' => 'boolean',
                'max_members' => 'nullable|integer|min:2|max:100',
            ];
        }
        
        if (str_contains($routePath, 'rooms') && $method === 'PUT') {
            return [
                'name' => 'sometimes|string|min:3|max:100|regex:/^[a-zA-Z0-9\s\-_]+$/',
                'description' => 'nullable|string|max:500',
                'is_private' => 'sometimes|boolean',
                'max_members' => 'nullable|integer|min:2|max:100',
            ];
        }
        
        // Message routes
        if (str_contains($routePath, 'messages') && $method === 'POST') {
            return [
                'room_id' => 'required|exists:rooms,id',
                'content' => 'required|string|min:1|max:1000',
                'type' => 'sometimes|in:text,image,file',
            ];
        }
        
        if (str_contains($routePath, 'messages') && $method === 'PUT') {
            return [
                'content' => 'required|string|min:1|max:1000',
            ];
        }
        
        // File upload routes
        if (str_contains($routePath, 'upload')) {
            return [
                'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip,rar',
                'room_id' => 'required|exists:rooms,id',
            ];
        }
        
        // Password reset routes
        if (str_contains($routePath, 'password/reset')) {
            return [
                'email' => 'required|email|exists:users,email',
            ];
        }
        
        if (str_contains($routePath, 'password/change')) {
            return [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'new_password_confirmation' => 'required|same:new_password',
            ];
        }
        
        // Notification routes
        if (str_contains($routePath, 'notifications/preferences')) {
            return [
                'email_notifications' => 'boolean',
                'push_notifications' => 'boolean',
                'message_notifications' => 'boolean',
                'room_invitation_notifications' => 'boolean',
                'security_alerts' => 'boolean',
            ];
        }
        
        // Bulk notification routes
        if (str_contains($routePath, 'notifications/bulk')) {
            return [
                'room_id' => 'required|exists:rooms,id',
                'message' => 'required|string|max:500',
                'type' => 'required|in:message,announcement,alert',
            ];
        }
        
        return [];
    }
    
    /**
     * Get custom validation messages.
     */
    protected function getCustomMessages(): array
    {
        return [
            'name.regex' => 'Name can only contain letters, numbers, spaces, hyphens, and underscores.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'email.unique' => 'This email address is already registered.',
            'email.exists' => 'No account found with this email address.',
            'file.max' => 'File size must not exceed 10MB.',
            'file.mimes' => 'File type not allowed. Allowed types: jpg, jpeg, png, gif, pdf, doc, docx, txt, zip, rar.',
            'room_id.exists' => 'Room not found.',
            'content.max' => 'Message content must not exceed 1000 characters.',
            'current_password.required' => 'Current password is required.',
            'new_password.regex' => 'New password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ];
    }
    
    /**
     * Sanitize validated data.
     */
    protected function sanitizeValidatedData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove extra whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Limit string length for security
                if (strlen($value) > 1000) {
                    $value = substr($value, 0, 1000);
                }
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
    
    /**
     * Validate file upload.
     */
    protected function validateFileUpload($file): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }
        
        // Check file size (10MB max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }
        
        // Check file type
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'application/zip', 'application/x-rar-compressed'
        ];
        
        return in_array($file->getMimeType(), $allowedTypes);
    }
    
    /**
     * Log validation failures for security monitoring.
     */
    protected function logValidationFailure(Request $request, array $errors): void
    {
        \Log::warning('Input validation failed', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'errors' => $errors,
            'user_id' => $request->user()?->id,
        ]);
    }
} 