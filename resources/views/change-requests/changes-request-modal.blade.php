<div class="row">
    <div class="col-md-12">
        <!-- Request Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('translation.request_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>{{ __('translation.request_type') }}:</strong>
                        <span class="badge badge-light-primary ms-2">{{ $data['request_type'] }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>{{ __('translation.status') }}:</strong>
                        @php
                            $statusClasses = [
                                'pending' => 'badge-light-warning',
                                'approved' => 'badge-light-success',
                                'rejected' => 'badge-light-danger',
                            ];
                            $statusClass = $statusClasses[$data['status']] ?? 'badge-light-warning';
                            
                            $statusLabels = [
                                'pending' => __('translation.pending'),
                                'approved' => __('translation.approved'),
                                'rejected' => __('translation.rejected'),
                            ];
                            $statusLabel = $statusLabels[$data['status']] ?? $data['status'];
                        @endphp
                        <span class="badge {{ $statusClass }} ms-2">{{ $statusLabel }}</span>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <strong>{{ __('translation.requester') }}:</strong>
                        <span class="ms-2">{{ $data['requester'] }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>{{ __('translation.created_at') }}:</strong>
                        <span class="ms-2">{{ $data['created_at'] }}</span>
                    </div>
                </div>
                @if($data['approver'])
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>{{ __('translation.approver') }}:</strong>
                            <span class="ms-2">{{ $data['approver'] }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('translation.approved_at') }}:</strong>
                            <span class="ms-2">{{ $data['approved_at'] ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                @endif
                @if($data['rejected_at'])
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>{{ __('translation.rejected_at') }}:</strong>
                            <span class="ms-2">{{ $data['rejected_at'] }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Reason -->
        @if($data['reason'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.reason') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $data['reason'] }}</p>
                </div>
            </div>
        @endif

        <!-- Moderation Note -->
        @if($data['moderation_note'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.moderation_note') }}</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $data['moderation_note'] }}</p>
                </div>
            </div>
        @endif

        <!-- Changes -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('translation.requested_changes') }}</h5>
            </div>
            <div class="card-body">
                @if($data['changes'] && count($data['changes']) > 0)
                    <div class="row">
                        @foreach($data['changes'] as $fieldName => $fieldData)
                            @if(is_array($fieldData) && isset($fieldData['current']) && isset($fieldData['requested']))
                                <div class="col-md-12 mb-4">
                                    <div class="card border border-warning">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title text-gray-800 fw-bold mb-0">
                                                {{ $fieldName }}
                                                <span class="badge badge-light-warning ms-2">{{ __('translation.changed') }}</span>
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <strong class="text-gray-700">{{ __('translation.current_value') }}:</strong>
                                                        <div class="mt-1 p-2 bg-warning-light rounded">
                                                            <span class="text-gray-600">{{ $fieldData['current'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <strong class="text-gray-700">{{ __('translation.requested_value') }}:</strong>
                                                        <div class="mt-1 p-2 bg-light-success rounded">
                                                            <span class="text-gray-600">{{ $fieldData['requested'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Fallback for old format (single value) -->
                                <div class="col-md-6 mb-3">
                                    <div class="card border border-gray-300">
                                        <div class="card-body p-3">
                                            <h6 class="card-title text-gray-800 fw-bold mb-2">{{ $fieldName }}</h6>
                                            <p class="card-text text-gray-600 mb-0">{{ $fieldData }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">{{ __('translation.no_changes_found') }}</p>
                @endif
            </div>
        </div>

        <!-- File Information -->
        @if($data['has_file'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.file_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="ki-duotone ki-information fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        {{ __('translation.this_request_includes_file_uploads') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
