jQuery(document).ready(function ($) {
    if (!$('#waza-reader').length) return;

    const html5QrCode = new Html5Qrcode("waza-reader");
    const $result = $('#waza-scanner-result');
    let isProcessing = false;

    const qrCodeSuccessCallback = (decodedText, decodedResult) => {
        if (isProcessing) return;
        isProcessing = true;

        // Stop scanning once code is found
        html5QrCode.pause();

        $result.html('<div class="waza-scanner-loading">' + wazaScanner.strings.verifying + '</div>');

        $.post(wazaScanner.ajax_url, {
            action: 'waza_verify_scanner_token',
            token: decodedText,
            nonce: wazaScanner.nonce
        }, function (response) {
            if (response.success) {
                const data = response.data;
                let html = '<div class="waza-scanner-success">';
                html += '<h3><span class="dashicons dashicons-yes-alt"></span> ' + data.message + '</h3>';
                html += '<div class="waza-verify-details">';
                html += '<p><strong>' + wazaScanner.strings.user + ':</strong> ' + data.user_name + ' (' + data.user_email + ')</p>';
                html += '<p><strong>' + wazaScanner.strings.activity + ':</strong> ' + data.slot_title + '</p>';
                html += '<p><strong>' + wazaScanner.strings.attendees + ':</strong> ' + data.attendees_count + '</p>';
                html += '</div>';
                html += '<button class="button button-primary waza-scan-again">' + wazaScanner.strings.next + '</button>';
                html += '</div>';
                $result.html(html);

                // Play success sound if any
            } else {
                let html = '<div class="waza-scanner-error">';
                html += '<h3><span class="dashicons dashicons-warning"></span> ' + wazaScanner.strings.error + '</h3>';
                html += '<p>' + response.data.message + '</p>';
                html += '<button class="button button-secondary waza-scan-again">' + wazaScanner.strings.retry + '</button>';
                html += '</div>';
                $result.html(html);
            }
        }).fail(function () {
            $result.html('<div class="waza-scanner-error">' + wazaScanner.strings.network_error + '</div>');
        }).always(function () {
            isProcessing = false;
        });
    };

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    // Start with the back camera
    html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);

    $(document).on('click', '.waza-scan-again', function () {
        $result.html('');
        html5QrCode.resume();
    });
});
