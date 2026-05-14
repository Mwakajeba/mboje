@extends('layouts.main')

@section('title', 'System Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'System Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        <h6 class="mb-0 text-uppercase">SYSTEM SETTINGS</h6>
        <hr/>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-x-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">System Configuration</h4>
                            <div>
                                @can('manage system configurations')
                                <button type="button" class="btn btn-warning btn-sm" onclick="confirmReset()">
                                    <i class="bx bx-refresh me-1"></i> Reset to Defaults
                                </button>
                                @endcan
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Settings
                                </a>
                            </div>
                        </div>

                        @can('edit system configurations')
                        <form action="{{ route('settings.system.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                        @else
                        <div>
                        @endcan
                            
                            <!-- Navigation Tabs -->
                            <ul class="nav nav-tabs nav-bordered" id="settingsTabs" role="tablist">
                                @foreach($groups as $groupKey => $groupName)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                                id="{{ $groupKey }}-tab" 
                                                data-bs-toggle="tab" 
                                                data-bs-target="#{{ $groupKey }}-content" 
                                                type="button" 
                                                role="tab">
                                            <i class="bx {{ $groupIcons[$groupKey] }} me-1"></i>
                                            {{ $groupName }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content mt-3" id="settingsTabContent">
                                @foreach($groups as $groupKey => $groupName)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                         id="{{ $groupKey }}-content" 
                                         role="tabpanel">
                                        
                                        @php
                                            // Organize security settings into sections
                                            if ($groupKey === 'security') {
                                                $passwordSettings = [];
                                                $sessionSettings = [];
                                                $loginSettings = [];
                                                $rateLimitSettings = [];
                                                $httpsSettings = [];
                                                $otherSettings = [];
                                                
                                                foreach ($settings[$groupKey] as $setting) {
                                                    if (str_starts_with($setting->key, 'password_')) {
                                                        $passwordSettings[] = $setting;
                                                    } elseif (str_starts_with($setting->key, 'session_')) {
                                                        $sessionSettings[] = $setting;
                                                    } elseif (str_starts_with($setting->key, 'login_') || $setting->key === 'lockout_duration' || $setting->key === 'two_factor_enabled') {
                                                        $loginSettings[] = $setting;
                                                    } elseif (str_starts_with($setting->key, 'rate_limit_')) {
                                                        $rateLimitSettings[] = $setting;
                                                    } elseif (str_starts_with($setting->key, 'security_')) {
                                                        $httpsSettings[] = $setting;
                                                    } else {
                                                        $otherSettings[] = $setting;
                                                    }
                                                }
                                                
                                                $organizedSettings = [
                                                    ['title' => 'Password Management', 'icon' => 'bx-lock-alt', 'color' => 'primary', 'description' => 'Configure password policies, history, expiration, and strength requirements', 'settings' => $passwordSettings],
                                                    ['title' => 'Session Settings', 'icon' => 'bx-time-five', 'color' => 'info', 'description' => 'Manage user session lifetime and security', 'settings' => $sessionSettings],
                                                    ['title' => 'Login Security', 'icon' => 'bx-log-in', 'color' => 'warning', 'description' => 'Configure login attempts, lockout policies, and two-factor authentication', 'settings' => $loginSettings],
                                                    ['title' => 'Rate Limiting', 'icon' => 'bx-tachometer', 'color' => 'success', 'description' => 'Control request rate limits for API, login, and other endpoints', 'settings' => $rateLimitSettings],
                                                    ['title' => 'HTTPS & Security Headers', 'icon' => 'bx-shield-quarter', 'color' => 'danger', 'description' => 'Enforce HTTPS and configure security headers', 'settings' => $httpsSettings],
                                                    ['title' => 'Other Security Settings', 'icon' => 'bx-cog', 'color' => 'secondary', 'description' => 'Additional security configurations', 'settings' => $otherSettings],
                                                ];
                                            } else {
                                                $organizedSettings = [['title' => '', 'settings' => $settings[$groupKey]]];
                                            }
                                        @endphp
                                        
                                        @if($groupKey === 'security')
                                            {{-- Professional Security Settings Layout --}}
                                            <div class="row g-4">
                                                @foreach($organizedSettings as $section)
                                                    @if(count($section['settings']) > 0)
                                                        <div class="col-12">
                                                            <div class="card border-0 shadow-sm h-100">
                                                                <div class="card-header bg-{{ $section['color'] }} bg-gradient text-white border-0">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="flex-shrink-0">
                                                                            <div class="avatar-sm bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                                                                <i class="bx {{ $section['icon'] }} fs-4"></i>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex-grow-1 ms-3">
                                                                            <h5 class="mb-0 fw-semibold">{{ $section['title'] }}</h5>
                                                                            @if(isset($section['description']))
                                                                                <small class="opacity-75">{{ $section['description'] }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body p-4">
                                                                    <div class="row g-3">
                                                                        @foreach($section['settings'] as $setting)
                                                                            <div class="col-md-6 col-lg-4">
                                                                                <div class="form-group mb-0">
                                                                                    <label for="{{ $setting->key }}" class="form-label fw-semibold mb-2">
                                                                                        {{ $setting->label }}
                                                                                        @if($setting->description)
                                                                                            <i class="bx bx-info-circle text-muted ms-1" 
                                                                                               data-bs-toggle="tooltip" 
                                                                                               data-bs-placement="top"
                                                                                               title="{{ $setting->description }}"></i>
                                                                                        @endif
                                                                                    </label>
                                                        
                                                                                    @if($setting->type === 'boolean')
                                                                                        <div class="form-check form-switch form-switch-lg">
                                                                                            <input class="form-check-input" 
                                                                                                   type="checkbox" 
                                                                                                   id="{{ $setting->key }}" 
                                                                                                   name="settings[{{ $setting->key }}]" 
                                                                                                   value="1" 
                                                                                                   {{ $setting->value ? 'checked' : '' }}
                                                                                                   @cannot('edit system configurations') disabled @endcannot>
                                                                                            <label class="form-check-label fw-normal" for="{{ $setting->key }}">
                                                                                                {{ $setting->value ? 'Enabled' : 'Disabled' }}
                                                                                            </label>
                                                                                        </div>
                                                                                        @if($setting->description)
                                                                                            <small class="text-muted d-block mt-1">{{ $setting->description }}</small>
                                                                                        @endif
                                                                                    @elseif($setting->type === 'integer')
                                                                                        <div class="input-group">
                                                                                            <input type="number" 
                                                                                                   class="form-control" 
                                                                                                   id="{{ $setting->key }}" 
                                                                                                   name="settings[{{ $setting->key }}]" 
                                                                                                   value="{{ $setting->value }}" 
                                                                                                   min="0"
                                                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                                                            @if(str_contains($setting->key, 'days') || str_contains($setting->key, 'day'))
                                                                                                <span class="input-group-text bg-light">days</span>
                                                                                            @elseif(str_contains($setting->key, 'minutes') || str_contains($setting->key, 'minute'))
                                                                                                <span class="input-group-text bg-light">min</span>
                                                                                            @elseif(str_contains($setting->key, 'seconds') || str_contains($setting->key, 'second'))
                                                                                                <span class="input-group-text bg-light">sec</span>
                                                                                            @elseif(str_contains($setting->key, 'count') || str_contains($setting->key, 'attempts'))
                                                                                                <span class="input-group-text bg-light">times</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if($setting->description)
                                                                                            <small class="text-muted d-block mt-1">{{ $setting->description }}</small>
                                                                                        @endif
                                                                                    @elseif($setting->key === 'timezone' && $groupKey === 'security')
                                                                                        <select class="form-select" 
                                                                                                id="{{ $setting->key }}" 
                                                                                                name="settings[{{ $setting->key }}]"
                                                                                                @cannot('edit system configurations') disabled @endcannot>
                                                                                            @foreach($timezones as $timezone)
                                                                                                <option value="{{ $timezone }}" 
                                                                                                        {{ $setting->value === $timezone ? 'selected' : '' }}>
                                                                                                    {{ $timezone }}
                                                                                                </option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                        @if($setting->description)
                                                                                            <small class="text-muted d-block mt-1">{{ $setting->description }}</small>
                                                                                        @endif
                                                                                    @elseif($setting->key === 'custom_password_blacklist' || $setting->key === 'password_custom_blacklist')
                                                                                        <textarea class="form-control" 
                                                                                                  id="{{ $setting->key }}" 
                                                                                                  name="settings[{{ $setting->key }}]" 
                                                                                                  rows="3"
                                                                                                  placeholder="Enter comma-separated passwords (e.g., password123, admin, 12345678)"
                                                                                                  @cannot('edit system configurations') readonly @endcannot>{{ $setting->value }}</textarea>
                                                                                        @if($setting->description)
                                                                                            <small class="text-muted d-block mt-1">{{ $setting->description }}</small>
                                                                                        @endif
                                                                                    @else
                                                                                        <input type="text" 
                                                                                               class="form-control" 
                                                                                               id="{{ $setting->key }}" 
                                                                                               name="settings[{{ $setting->key }}]" 
                                                                                               value="{{ $setting->value }}" 
                                                                                               placeholder="Enter {{ strtolower($setting->label) }}"
                                                                                               @cannot('edit system configurations') readonly @endcannot>
                                                                                        @if($setting->description)
                                                                                            <small class="text-muted d-block mt-1">{{ $setting->description }}</small>
                                                                                        @endif
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Standard Layout for Other Tabs --}}
                                        <div class="row">
                                            @foreach($settings[$groupKey] as $setting)
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-group">
                                                        <label for="{{ $setting->key }}" class="form-label">
                                                            {{ $setting->label }}
                                                            @if($setting->description)
                                                                <i class="bx bx-info-circle text-muted" 
                                                                   data-bs-toggle="tooltip" 
                                                                   title="{{ $setting->description }}"></i>
                                                            @endif
                                                        </label>
                                                        
                                                        @if($setting->type === 'boolean')
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" 
                                                                       type="checkbox" 
                                                                       id="{{ $setting->key }}" 
                                                                       name="settings[{{ $setting->key }}]" 
                                                                       value="1" 
                                                                       {{ $setting->value ? 'checked' : '' }}
                                                                       @cannot('edit system configurations') disabled @endcannot>
                                                                <label class="form-check-label" for="{{ $setting->key }}">
                                                                    Enable {{ $setting->label }}
                                                                </label>
                                                            </div>
                                                        @elseif($setting->type === 'integer')
                                                            <input type="number" 
                                                                   class="form-control" 
                                                                   id="{{ $setting->key }}" 
                                                                   name="settings[{{ $setting->key }}]" 
                                                                   value="{{ $setting->value }}" 
                                                                   min="0"
                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                        @elseif($setting->key === 'timezone')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                @foreach($timezones as $timezone)
                                                                    <option value="{{ $timezone }}" 
                                                                            {{ $setting->value === $timezone ? 'selected' : '' }}>
                                                                        {{ $timezone }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($setting->key === 'locale')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                <option value="en" {{ $setting->value === 'en' ? 'selected' : '' }}>English</option>
                                                                <option value="sw" {{ $setting->value === 'sw' ? 'selected' : '' }}>Swahili</option>
                                                                <option value="fr" {{ $setting->value === 'fr' ? 'selected' : '' }}>French</option>
                                                                <option value="es" {{ $setting->value === 'es' ? 'selected' : '' }}>Spanish</option>
                                                                <option value="ar" {{ $setting->value === 'ar' ? 'selected' : '' }}>Arabic</option>
                                                            </select>
                                                        @elseif($setting->key === 'currency')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                <option value="TZS" {{ $setting->value === 'TZS' ? 'selected' : '' }}>TZS - Tanzania Shilling</option>
                                                                <option value="USD" {{ $setting->value === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                                                <option value="EUR" {{ $setting->value === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                                                <option value="GBP" {{ $setting->value === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                                                <option value="JPY" {{ $setting->value === 'JPY' ? 'selected' : '' }}>JPY - Japanese Yen</option>
                                                                <option value="CAD" {{ $setting->value === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                                                <option value="AUD" {{ $setting->value === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                                                <option value="CHF" {{ $setting->value === 'CHF' ? 'selected' : '' }}>CHF - Swiss Franc</option>
                                                                <option value="CNY" {{ $setting->value === 'CNY' ? 'selected' : '' }}>CNY - Chinese Yuan</option>
                                                                <option value="INR" {{ $setting->value === 'INR' ? 'selected' : '' }}>INR - Indian Rupee</option>
                                                                <option value="BRL" {{ $setting->value === 'BRL' ? 'selected' : '' }}>BRL - Brazilian Real</option>
                                                                <option value="KES" {{ $setting->value === 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                                                <option value="UGX" {{ $setting->value === 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                                                                <option value="ZAR" {{ $setting->value === 'ZAR' ? 'selected' : '' }}>ZAR - South African Rand</option>
                                                            </select>
                                                        @elseif($setting->key === 'backup_frequency')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                <option value="hourly" {{ $setting->value === 'hourly' ? 'selected' : '' }}>Hourly</option>
                                                                <option value="daily" {{ $setting->value === 'daily' ? 'selected' : '' }}>Daily</option>
                                                                <option value="weekly" {{ $setting->value === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                                <option value="monthly" {{ $setting->value === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                            </select>
                                                        @elseif($setting->key === 'log_level')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                <option value="emergency" {{ $setting->value === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                                                <option value="alert" {{ $setting->value === 'alert' ? 'selected' : '' }}>Alert</option>
                                                                <option value="critical" {{ $setting->value === 'critical' ? 'selected' : '' }}>Critical</option>
                                                                <option value="error" {{ $setting->value === 'error' ? 'selected' : '' }}>Error</option>
                                                                <option value="warning" {{ $setting->value === 'warning' ? 'selected' : '' }}>Warning</option>
                                                                <option value="notice" {{ $setting->value === 'notice' ? 'selected' : '' }}>Notice</option>
                                                                <option value="info" {{ $setting->value === 'info' ? 'selected' : '' }}>Info</option>
                                                                <option value="debug" {{ $setting->value === 'debug' ? 'selected' : '' }}>Debug</option>
                                                            </select>
                                                        @elseif($setting->key === 'mail_encryption')
                                                            <select class="form-select" 
                                                                    id="{{ $setting->key }}" 
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                <option value="tls" {{ $setting->value === 'tls' ? 'selected' : '' }}>TLS</option>
                                                                <option value="ssl" {{ $setting->value === 'ssl' ? 'selected' : '' }}>SSL</option>
                                                                <option value="" {{ $setting->value === '' ? 'selected' : '' }}>None</option>
                                                            </select>
                                                        @elseif($setting->key === 'mail_from_address')
                                                            <div class="input-group">
                                                                <input type="email" 
                                                                       class="form-control" 
                                                                       id="{{ $setting->key }}" 
                                                                       name="settings[{{ $setting->key }}]" 
                                                                       value="{{ $setting->value }}" 
                                                                       placeholder="Enter email address"
                                                                       @cannot('edit system configurations') readonly @endcannot>
                                                                @can('edit system configurations')
                                                                <button type="button" class="btn btn-outline-primary" onclick="testEmailConfig()">
                                                                    <i class="bx bx-send me-1"></i> Test
                                                                </button>
                                                                @endcan
                                                            </div>
                                                        @elseif(in_array($setting->key, ['document_page_size']))
                                                            <select class="form-select"
                                                                    id="{{ $setting->key }}"
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                @php($sizes = ['A3','A4','A5','A6','Letter','Legal'])
                                                                @foreach($sizes as $size)
                                                                    <option value="{{ $size }}" {{ $setting->value === $size ? 'selected' : '' }}>{{ $size }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif(in_array($setting->key, ['document_orientation']))
                                                            <select class="form-select"
                                                                    id="{{ $setting->key }}"
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                @foreach(['portrait','landscape'] as $opt)
                                                                    <option value="{{ $opt }}" {{ $setting->value === $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif(in_array($setting->key, ['document_font_family']))
                                                            <select class="form-select"
                                                                    id="{{ $setting->key }}"
                                                                    name="settings[{{ $setting->key }}]"
                                                                    @cannot('edit system configurations') disabled @endcannot>
                                                                @php($fonts = ['DejaVu Sans','Arial','Helvetica','Times New Roman','Courier New'])
                                                                @foreach($fonts as $font)
                                                                    <option value="{{ $font }}" {{ $setting->value === $font ? 'selected' : '' }}>{{ $font }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif(in_array($setting->key, ['document_base_font_size']))
                                                            <input type="number"
                                                                   class="form-control"
                                                                   id="{{ $setting->key }}"
                                                                   name="settings[{{ $setting->key }}]"
                                                                   value="{{ $setting->value }}"
                                                                   min="6" max="24"
                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                        @elseif(in_array($setting->key, ['document_line_height']))
                                                            <input type="number"
                                                                   class="form-control"
                                                                   id="{{ $setting->key }}"
                                                                   name="settings[{{ $setting->key }}]"
                                                                   value="{{ $setting->value }}"
                                                                   step="0.1" min="1" max="2"
                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                        @elseif(in_array($setting->key, ['document_text_color','document_background_color','document_header_color','document_accent_color','document_table_header_bg','document_table_header_text']))
                                                            <input type="color"
                                                                   class="form-control form-control-color"
                                                                   id="{{ $setting->key }}"
                                                                   name="settings[{{ $setting->key }}]"
                                                                   value="{{ $setting->value }}"
                                                                   title="Choose color"
                                                                   @cannot('edit system configurations') disabled @endcannot>
                                                        @elseif(in_array($setting->key, ['document_margin_top','document_margin_right','document_margin_bottom','document_margin_left']))
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="{{ $setting->key }}" 
                                                                   name="settings[{{ $setting->key }}]" 
                                                                   value="{{ $setting->value }}" 
                                                                   placeholder="e.g., 1.0cm"
                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                        @else
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="{{ $setting->key }}" 
                                                                   name="settings[{{ $setting->key }}]" 
                                                                   value="{{ $setting->value }}" 
                                                                   placeholder="Enter {{ strtolower($setting->label) }}"
                                                                   @cannot('edit system configurations') readonly @endcannot>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @can('edit system configurations')
                            <div class="mt-4">
                                {!! form_submit_button(__('app.save_settings'), 'btn btn-primary', 'bx bx-save') !!}
                                <button type="reset" class="btn btn-secondary">
                                    <i class="bx bx-reset me-1"></i> {{ __('app.reset_form') }}
                                </button>
                            </div>
                            @endcan
                        @can('edit system configurations')
                        </form>
                        @else
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset System Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset all system settings to their default values?</p>
                <p class="text-warning"><i class="bx bx-warning me-1"></i> This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                @can('manage system configurations')
                <form action="{{ route('settings.system.reset') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirmDelete(this.form, '{{ __('app.are_you_sure_reset_settings') }}')">{{ __('app.reset_to_defaults') }}</button>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Professional Security Settings Styling */
    .security-settings-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .security-settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .security-card-header {
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    
    .avatar-sm {
        width: 48px;
        height: 48px;
    }
    
    .form-switch-lg .form-check-input {
        width: 3rem;
        height: 1.5rem;
    }
    
    .form-switch-lg .form-check-input:checked {
        background-color: #0d6efd;
    }
    
    .input-group-text {
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .security-section-icon {
        font-size: 1.5rem;
    }
    
    /* Badge for enabled/disabled status */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .status-enabled {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .status-disabled {
        background-color: #f8d7da;
        color: #842029;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Update switch labels dynamically for security settings
    const securitySwitches = document.querySelectorAll('#security-content .form-switch-lg input[type="checkbox"]');
    securitySwitches.forEach(function(switchEl) {
        const label = switchEl.nextElementSibling;
        if (label) {
            switchEl.addEventListener('change', function() {
                label.textContent = this.checked ? 'Enabled' : 'Disabled';
            });
        }
    });

    // Add visual feedback for security cards
    const securityCards = document.querySelectorAll('#security-content .card');
    securityCards.forEach(function(card) {
        card.classList.add('security-settings-card');
    });

    // Show password requirements for security settings
    showPasswordRequirements();
    
    // Auto-save functionality (optional)
    let autoSaveTimer;
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                // Show auto-save indicator
                const saveBtn = form.querySelector('button[type="submit"]');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Auto-saving...';
                saveBtn.disabled = true;
                
                // Auto-save after 2 seconds of inactivity
                setTimeout(() => {
                    form.submit();
                }, 2000);
            }, 2000);
        });
    });
});

function showPasswordRequirements() {
    // Get current password settings
    const minLength = document.getElementById('password_min_length')?.value || 8;
    const requireSpecial = document.getElementById('password_require_special')?.checked || false;
    const requireNumbers = document.getElementById('password_require_numbers')?.checked || false;
    const requireUppercase = document.getElementById('password_require_uppercase')?.checked || false;
    
    // Create requirements text
    let requirements = [`Minimum ${minLength} characters`];
    if (requireUppercase) requirements.push('At least one uppercase letter');
    if (requireNumbers) requirements.push('At least one number');
    if (requireSpecial) requirements.push('At least one special character');
    
    // Show requirements in security tab
    const securityTab = document.getElementById('security-content');
    if (securityTab) {
        const requirementsDiv = securityTab.querySelector('.password-requirements');
        if (!requirementsDiv) {
            const div = document.createElement('div');
            div.className = 'alert alert-info password-requirements mt-3';
            div.innerHTML = '<strong>Current Password Requirements:</strong><br>' + requirements.join('<br>');
            securityTab.appendChild(div);
        } else {
            requirementsDiv.innerHTML = '<strong>Current Password Requirements:</strong><br>' + requirements.join('<br>');
        }
    }
}

function confirmReset() {
    var resetModal = new bootstrap.Modal(document.getElementById('resetModal'));
    resetModal.show();
}

// Test email configuration
function testEmailConfig() {
    const emailField = document.getElementById('mail_from_address');
    const email = emailField.value;
    
    if (!email) {
        alert('Please enter an email address first.');
        return;
    }
    
    // Show loading state
    const testBtn = event.target;
    const originalText = testBtn.innerHTML;
    testBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...';
    testBtn.disabled = true;
    
    // Make AJAX request
    fetch('{{ route("settings.system.test-email") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email test successful!');
        } else {
            alert('Email test failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Email test failed: ' + error.message);
    })
    .finally(() => {
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
    });
}
</script>
@endpush 