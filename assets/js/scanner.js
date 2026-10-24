document.addEventListener('DOMContentLoaded', function () {
    var startScanBtn = document.getElementById('startScanBtn');
    var readerBox = document.getElementById('qrReader');
    var codeInput = document.getElementById('bookingCodeInput');
    var scanForm = document.getElementById('validateTicketForm');
    var scanner = null;

    if (!startScanBtn || !readerBox) return;

    startScanBtn.addEventListener('click', function () {
        if (typeof Html5Qrcode === 'undefined') {
            alert('Library scanner kamera belum termuat. Gunakan input kode manual.');
            return;
        }

        readerBox.style.display = 'block';
        startScanBtn.disabled = true;
        startScanBtn.textContent = 'Kamera Aktif...';

        scanner = new Html5Qrcode('qrReader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            function (decodedText) {
                codeInput.value = decodedText.trim();
                scanner.stop().then(function () {
                    scanForm.submit();
                });
            },
            function () {}
        ).catch(function (err) {
            alert('Tidak dapat mengakses kamera: ' + err);
            startScanBtn.disabled = false;
            startScanBtn.textContent = 'Scan QR via Kamera';
        });
    });
});
