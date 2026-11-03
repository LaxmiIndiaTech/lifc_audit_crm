<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIFC - Branch Inspection Report Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        .card-custom { border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: none; margin-bottom: 25px; }
        .header-box { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .table-custom thead { background-color: #2c3e50; color: white; }
        .badge-grade { font-size: 1.1rem; padding: 8px 16px; border-radius: 20px; }
        .badge-excellent { background-color: #28a745; color: white; }
        .badge-good { background-color: #17a2b8; color: white; }
        .badge-satisfactory { background-color: #ffc107; color: black; }
        .badge-unsatisfactory { background-color: #dc3545; color: white; }
        .section-header { background-color: #e9ecef; font-weight: bold; }
    </style>
</head>
<body class="p-4">
<div class="container-fluid max-w-7xl">

    <!-- Header Box -->
    <div class="header-box d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1"><i class="fa-solid fa-clipboard-check me-2"></i>Branch Inspection Report Card</h2>
            <h5 class="opacity-75 mb-0">Laxmi India Finance Ltd.</h5>
        </div>
        <div class="text-end">
            <span class="badge {{ $inspection->process_score_pct > 90 ? 'badge-excellent' : ($inspection->process_score_pct > 65 ? 'badge-good' : ($inspection->process_score_pct > 50 ? 'badge-satisfactory' : 'badge-unsatisfactory')) }} badge-grade">
                Grade: {{ $inspection->final_grade }} ({{ number_format($inspection->process_score_pct, 2) }}%)
            </span>
            <div class="small mt-1 opacity-75">Quarter: {{ $inspection->financial_quarter }} | FY: {{ $inspection->financial_year }}</div>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Branch Details & Upload Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-building me-2"></i>Branch Information</h5>
                <table class="table table-sm table-borderless">
                    <tr><th width="35%">Branch Name:</th><td><strong>{{ $branch->branch_name }}</strong></td></tr>
                    <tr><th>Address:</th><td>{{ $branch->address }}</td></tr>
                    <tr><th>Branch In-charge:</th><td>{{ $inspection->branch_in_charge_name }} ({{ $inspection->branch_in_charge_code }})</td></tr>
                    <tr><th>Cluster Head:</th><td>{{ $inspection->cluster_name }} ({{ $inspection->cluster_code }})</td></tr>
                    <tr><th>Inspection Date:</th><td>{{ $inspection->inspection_date->format('Y-m-d') }}</td></tr>
                    <tr><th>Inspection Officials:</th><td>{{ $inspection->inspection_officials }}</td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-file-arrow-up me-2"></i>Automated Sheet Uploads</h5>
                
                <!-- A.1 Upload -->
                <form action="{{ route('web.upload_receipts') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                    <label class="form-label fw-semibold small">Upload LMS Receipts Sheet (A.1 - T+1 Auto Score)</label>
                    <div class="input-group">
                        <input type="file" name="file" class="form-control form-control-sm" required>
                        <button class="btn btn-primary btn-sm" type="submit">Upload & Score A.1</button>
                    </div>
                </form>

                <!-- A.2 Upload -->
                <form action="{{ route('web.upload_personal') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                    <label class="form-label fw-semibold small">Upload Personal Account Transactions (A.2 - Auto Score)</label>
                    <div class="input-group">
                        <input type="file" name="file" class="form-control form-control-sm" required>
                        <button class="btn btn-warning btn-sm" type="submit">Upload & Score A.2</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Section-wise Marks Summary Table -->
    <div class="card card-custom p-4">
        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-chart-pie me-2"></i>Section I - Process Marks Summary (Total 40 Marks)</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-custom text-center align-middle">
                <thead>
                    <tr>
                        <th width="8%">Section</th>
                        <th class="text-start">Section Name</th>
                        <th width="15%">Marks Carry</th>
                        <th width="15%">Marks Obtained</th>
                        <th width="15%">% Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['sections'] as $sec)
                    <tr>
                        <td><strong>{{ $sec['section_code'] }}</strong></td>
                        <td class="text-start">{{ $sec['section_name'] }}</td>
                        <td>{{ number_format($sec['marks_carry'], 2) }}</td>
                        <td><strong class="text-primary">{{ number_format($sec['marks_obtained'], 2) }}</strong></td>
                        <td>{{ number_format($sec['percentage'], 2) }}%</td>
                    </tr>
                    @endforeach
                    <tr class="table-primary fw-bold">
                        <td colspan="2" class="text-start">SUB TOTAL I (PROCESS)</td>
                        <td>{{ number_format($summary['sub_total_i']['marks_carry'], 2) }}</td>
                        <td>{{ number_format($summary['sub_total_i']['marks_obtained'], 2) }}</td>
                        <td>{{ number_format($summary['sub_total_i']['percentage'], 2) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Annex 1: Detailed Check List & Manual Marks Entry -->
    <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-list-check me-2"></i>Annex 1 - Checklist & Score Details (A.1 to G.4)</h5>
            <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>A.1 & A.2 are auto-calculated; B to G can be edited below.</small>
        </div>

        <form action="{{ route('web.save_scores', $inspection->id) }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">Code</th>
                            <th width="28%">Scope Point</th>
                            <th width="6%">Carry</th>
                            <th width="8%">Obtain</th>
                            <th width="20%">Visitor Comment</th>
                            <th width="20%">Immediate Action</th>
                            <th width="8%">Dept</th>
                            <th width="5%">Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inspection->scores()->orderBy('checkpoint_code')->get() as $idx => $score)
                        <tr class="{{ in_array($score->checkpoint_code, ['A.1', 'A.2']) ? 'table-light' : '' }}">
                            <td class="text-center fw-bold">{{ $score->checkpoint_code }}</td>
                            <td class="small">{{ $score->scope_point }}</td>
                            <td class="text-center">{{ number_format($score->carry_marks, 2) }}</td>
                            <td>
                                @if(in_array($score->checkpoint_code, ['A.1', 'A.2']))
                                    <input type="text" class="form-control form-control-sm text-center fw-bold bg-light" 
                                           value="{{ number_format($score->marks_obtained, 2) }}" readonly title="Auto-calculated from uploaded sheet">
                                @else
                                    <input type="hidden" name="scores[{{ $idx }}][checkpoint_code]" value="{{ $score->checkpoint_code }}">
                                    <input type="number" step="0.01" min="0" max="{{ $score->carry_marks }}" 
                                           name="scores[{{ $idx }}][marks_obtained]" 
                                           class="form-control form-control-sm text-center fw-bold" 
                                           value="{{ $score->marks_obtained }}">
                                @endif
                            </td>
                            <td>
                                @if(in_array($score->checkpoint_code, ['A.1', 'A.2']))
                                    <span class="small text-muted">{{ $score->visitor_comment }}</span>
                                @else
                                    <textarea name="scores[{{ $idx }}][visitor_comment]" rows="2" class="form-control form-control-sm small">{{ $score->visitor_comment }}</textarea>
                                @endif
                            </td>
                            <td>
                                @if(in_array($score->checkpoint_code, ['A.1', 'A.2']))
                                    <span class="small text-muted">{{ $score->immediate_action }}</span>
                                @else
                                    <textarea name="scores[{{ $idx }}][immediate_action]" rows="2" class="form-control form-control-sm small">{{ $score->immediate_action }}</textarea>
                                @endif
                            </td>
                            <td>
                                @if(in_array($score->checkpoint_code, ['A.1', 'A.2']))
                                    <span class="badge bg-secondary">{{ $score->department }}</span>
                                @else
                                    <select name="scores[{{ $idx }}][department]" class="form-select form-select-sm">
                                        <option value="Ops" {{ $score->department == 'Ops' ? 'selected' : '' }}>Ops</option>
                                        <option value="Admin" {{ $score->department == 'Admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="IT" {{ $score->department == 'IT' ? 'selected' : '' }}>IT</option>
                                        <option value="CMS" {{ $score->department == 'CMS' ? 'selected' : '' }}>CMS</option>
                                    </select>
                                @endif
                            </td>
                            <td class="text-center small">{{ number_format($score->weight, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success px-4"><i class="fa-solid fa-save me-2"></i>Save Manual Scores (B to G) & Recalculate</button>
            </div>
        </form>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
