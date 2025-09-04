<!DOCTYPE html>
<html lang="en">

<head>
    <title>BILLING AND COLLECTION</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <!--===============================================================================================-->
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" type="text/css" href="assets/css/util.css">
    <link rel="stylesheet" type="text/css" href="assets/css/main.css">
    <!--===============================================================================================-->
    <style>
        body, .login100-form-title, .input100, .txt1, label, .modal-content, .modal-title, .modal-body, .modal-footer, .login100-form-btn {
            color: #fff !important;
        }
        .modal-content, .modal-title, .modal-body, .modal-footer {
            color: #222 !important;
            background: #fff !important;
        }
        img {
            height: auto;
            width: 255px;
        }
    </style>
</head>

<body>
    <div class="limiter">
        <div class="container-login100" style="background-image: url('assets/images/bg-01.jpg');">
            <div class="wrap-login100">
                <form method="POST" action="{{ route('login') }}" class="login100-form validate-form">
                    @csrf

                    <span class="login100-form-logo">
                        <img src="assets/images/logo.png" alt="">
                    </span>

                    <span class="login100-form-title p-b-34 p-t-27">
                        BILLING AND COLLECTION
                    </span>

                    <div class="wrap-input100 validate-input" data-validate="Enter email">
                        <input class="input100" type="email" name="email" placeholder="Email" required>
                        <span class="focus-input100" data-placeholder="&#xf207;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Enter password">
                        <input class="input100" type="password" name="password" placeholder="Password" required>
                        <span class="focus-input100" data-placeholder="&#xf191;"></span>
                    </div>


                    <div class="container-login100-form-btn">
                        <button type="submit" class="login100-form-btn">
                            Login
                        </button>
                    </div>

                    <div class="text-center p-t-90">
                        <a class="txt1" href="#" data-toggle="modal" data-target="#privacyPolicyModal">
                            Privacy Policy
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyPolicyModal" tabindex="-1" role="dialog" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="privacyPolicyModalLabel">Privacy Policy</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>This application does not collect, store, or use any personal data from users. All information entered is used solely for authentication and is not shared with third parties. No tracking or analytics are implemented. For questions, contact the system administrator.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap 4 JS and CSS for modal functionality (CDN) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Unauthorized',
                text: '{{ session('error') }}',
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '{{ $errors->first() }}',
            });
        @endif
    </script>

</body>

</html>
