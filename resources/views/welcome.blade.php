<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <!-- Styles -->
</head>
<body>
<div class="container">
    <div class="row justify-content-center" style="margin-top: 100px;">
        <div class="col-sm-6">
            <form method="post" action="{{action('PaymentController@makePayment')}}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" name="name" id="name" aria-describedby="emailHelp"
                           placeholder="Name">
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" class="form-control" name="amount" id="amount" aria-describedby="emailHelp"
                           placeholder="Amount" step="any">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">
                    <i class="fa fa-paypal"></i>
                    Make Payment
                </button>
            </form>
        </div>
    </div>
</div>
</body>
<script src="{{ asset('js/app.js') }}" defer></script>
</html>
