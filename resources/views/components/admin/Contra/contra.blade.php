<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contra Account - Admin</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border: 1px solid #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <div id="main-wrapper">
        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')
        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Contra Account (Admin)</h4>
                            <span class="ml-1">Create Contra Account Link</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form method="POST" action="{{ route('admin.contra') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="type">Type</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="">Select Type</option>
                                            <option value="shares">Shares</option>
                                            <option value="savings">Savings</option>
                                            <option value="loans">Loans</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="account-input-group">
                                        <label for="account_numbers">Account(s)</label>
                                        <select class="form-control" id="account_numbers" name="account_numbers[]" multiple="multiple" required style="width:100%"></select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.partials.footer')
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#account_numbers').select2({
                placeholder: 'Select account(s)',
                allowClear: true,
                width: 'resolve'
            });
            $('#type').on('change', function() {
                var type = $(this).val();
                $('#account_numbers').empty().trigger('change');
                if (type) {
                    $.ajax({
                        url: '{{ route('admin.contra.accounts') }}',
                        data: { type: type },
                        success: function(data) {
                            var options = [];
                            data.forEach(function(account) {
                                options.push({ id: account, text: account });
                            });
                            $('#account_numbers').select2({
                                data: options,
                                placeholder: 'Select account(s)',
                                allowClear: true,
                                width: 'resolve'
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
