@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mx-0">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4>Share Accounts</h4>
                <span class="ml-1">View share accounts</span>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.branch') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Shares</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Share Accounts</h4>
                    <div class="d-flex align-items-center">
                        <form method="GET" action="{{ url()->current() }}" class="me-2">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search accounts..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Account Number</th>
                                    <th>Member Name</th>
                                    <th>CID</th>
                                    <th>Product</th>
                                    <th>Current Balance</th>
                                    <th>Available Balance</th>
                                    <th>Interest</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shares as $share)
                                <tr>
                                    <td>{{ $share->account_number }}</td>
                                    <td>{{ $share->member->fname }} {{ $share->member->lname }}</td>
                                    <td>{{ $share->member->cid }}</td>
                                    <td>{{ $share->product_name ?? 'N/A' }}</td>
                                    <td>₱{{ number_format($share->current_balance, 2) }}</td>
                                    <td>₱{{ number_format($share->available_balance ?? 0, 2) }}</td>
                                    <td>₱{{ number_format($share->interest ?? 0, 2) }}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewModal"
                                            data-id="{{ $share->id }}"
                                            data-account_number="{{ $share->account_number }}"
                                            data-member_name="{{ $share->member->fname }} {{ $share->member->lname }}"
                                            data-cid="{{ $share->member->cid }}"
                                            data-product_code="{{ $share->product_code }}"
                                            data-product_name="{{ $share->product_name }}"
                                            data-open_date="{{ $share->open_date }}"
                                            data-current_balance="{{ $share->current_balance }}"
                                            data-available_balance="{{ $share->available_balance }}"
                                            data-interest="{{ $share->interest }}">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        {{ $shares->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Share Account</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Account Number:</strong> <span id="view-account_number"></span></p>
                        <p><strong>Member Name:</strong> <span id="view-member_name"></span></p>
                        <p><strong>CID:</strong> <span id="view-cid"></span></p>
                        <p><strong>Product Code:</strong> <span id="view-product_code"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Product Name:</strong> <span id="view-product_name"></span></p>
                        <p><strong>Open Date:</strong> <span id="view-open_date"></span></p>
                        <p><strong>Current Balance:</strong> ₱<span id="view-current_balance"></span></p>
                        <p><strong>Available Balance:</strong> ₱<span id="view-available_balance"></span></p>
                        <p><strong>Interest:</strong> ₱<span id="view-interest"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // View Modal
    $('#viewModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);

        $('#view-account_number').text(button.data('account_number'));
        $('#view-member_name').text(button.data('member_name'));
        $('#view-cid').text(button.data('cid'));
        $('#view-product_code').text(button.data('product_code') || 'N/A');
        $('#view-product_name').text(button.data('product_name') || 'N/A');
        $('#view-open_date').text(button.data('open_date'));
        $('#view-current_balance').text(number_format(button.data('current_balance'), 2));
        $('#view-available_balance').text(number_format(button.data('available_balance') || 0, 2));
        $('#view-interest').text(number_format(button.data('interest') || 0, 2));
    });

    function number_format(number, decimals) {
        return parseFloat(number).toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
</script>
@endpush
