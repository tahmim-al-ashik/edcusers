<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
    <title>Upay Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta content="IE=Edge,chrom=1" http-equiv="X-UA-Compatible"/>

    <style>
        html {margin: 0;padding: 0;}
        p {text-align: center;}
    </style>
</head>

<body>
<div style="display: flex;justify-content: center;align-items: center; height: 100vh;flex-direction: column">
    <div style="font-weight: bold">Please wait...</div>
    <div>validating your transaction</div>
</div>

<script type="text/javascript">

    window.addEventListener(
        "flutterInAppWebViewPlatformReady",
        function (event) {
            window.flutter_inappwebview.callHandler("paymentData").then(function (data) {
                window.flutter_inappwebview.callHandler('paymentRedirectResponse', <?php echo json_encode($data); ?>);
            });
        }
    );

</script>
</body>
</html>
