<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\AuditLogger;

class SettingController extends Controller
{
    /**
     * Retrieve all settings.
     */
    public function index()
    {
        $settings = Setting::pluck('value', 'key');
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Insert or update settings.
     * Expects payload like: { "settings": { "mail_host": "smtp.mailtrap.io", "mail_port": "2525" } }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable'
        ]);

        $userId = auth()->id();

        foreach ($data['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'create_by' => $userId,
                    'update_by' => $userId,
                ]);
            } else {
                $setting->update([
                    'value' => $value,
                    'update_by' => $userId,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully.'
        ]);
    }

    /**
     * Retrieve SMTP settings.
     */
    public function getSmtp()
    {
        $keys = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ];
        
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Insert or update SMTP settings specifically.
     */
    public function storeSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_mailer' => 'nullable|string',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|string',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'nullable|string',
            'mail_from_name' => 'nullable|string',
        ]);

        $userId = auth()->id();

        $oldValues = Setting::whereIn('key', array_keys($data))->pluck('value', 'key')->toArray();

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'create_by' => $userId,
                    'update_by' => $userId,
                ]);
            } else {
                $setting->update([
                    'value' => $value,
                    'update_by' => $userId,
                ]);
            }
        }

        AuditLogger::log('Settings Changed', 'SMTP Settings Changed', 'Admin updated SMTP settings.', $oldValues, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'SMTP settings updated successfully.'
        ]);
    }

    /**
     * Retrieve General settings.
     */
    public function getGeneral()
    {
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_phone',
            'company_email',
            'company_address',
            'company_gst',
            'company_tagline'
        ];
        
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        foreach (['company_favicon', 'company_short_logo', 'company_logo'] as $fileField) {
            if (isset($settings[$fileField]) && !empty($settings[$fileField])) {
                if (!str_starts_with($settings[$fileField], 'http')) {
                    $settings[$fileField] = asset($settings[$fileField]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Insert or update General settings.
     * Supports file uploads for logos and favicon.
     */
    public function storeGeneral(Request $request)
    {
        $rules = [
            'company_name' => 'nullable|string',
            'company_phone' => 'nullable|string',
            'company_email' => 'nullable|email',
            'company_address' => 'nullable|string',
            'company_gst' => 'nullable|string',
            'company_tagline' => 'nullable|string',
        ];
        
        // If file is sent, validate as image
        if ($request->hasFile('company_favicon')) {
            $rules['company_favicon'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:2048';
        } else {
            $rules['company_favicon'] = 'nullable|string';
        }

        if ($request->hasFile('company_short_logo')) {
            $rules['company_short_logo'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
        } else {
            $rules['company_short_logo'] = 'nullable|string';
        }

        if ($request->hasFile('company_logo')) {
            $rules['company_logo'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
        } else {
            $rules['company_logo'] = 'nullable|string';
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle file uploads
        foreach (['company_favicon', 'company_short_logo', 'company_logo'] as $fileField) {
            if ($request->hasFile($fileField)) {
                $path = $request->file($fileField)->store('settings', 'public');
                $data[$fileField] = '/storage/' . $path;
            }
        }

        $userId = auth()->id();
        $oldValues = Setting::whereIn('key', array_keys($data))->pluck('value', 'key')->toArray();
        $newValues = [];

        foreach ($data as $key => $value) {
            // If value is null and it's a file field, maybe we don't want to overwrite if they didn't upload a new one?
            // Usually, if a user updates a form without choosing a new file, it shouldn't clear the old one.
            if ($value === null && in_array($key, ['company_favicon', 'company_short_logo', 'company_logo'])) {
                continue; // Skip updating file fields if no new file/value is provided
            }

            $newValues[$key] = $value;
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'create_by' => $userId,
                    'update_by' => $userId,
                ]);
            } else {
                $setting->update([
                    'value' => $value,
                    'update_by' => $userId,
                ]);
            }
        }

        if (!empty($newValues)) {
            AuditLogger::log('Settings Changed', 'System Settings Changed', 'Admin updated General settings.', $oldValues, $newValues);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'General settings updated successfully.'
        ]);
    }
}
