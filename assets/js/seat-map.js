document.addEventListener('DOMContentLoaded', function () {
    var seatButtons = document.querySelectorAll('.seat-btn:not(:disabled)');
    var selectedIdsInput = document.getElementById('selectedSeatIds');
    var selectedLabelsEl = document.getElementById('selectedSeatLabels');
    var totalPriceEl = document.getElementById('totalPriceDisplay');
    var submitBtn = document.getElementById('submitSeatsBtn');
    var seatMapWrap = document.getElementById('seatMapContainer');

    if (!seatMapWrap) return;

    var pricePerSeat = parseInt(seatMapWrap.getAttribute('data-price') || '0', 10);
    var maxSeats = 6;
    var selectedSeats = [];

    function formatRupiah(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function updateSummary() {
        if (selectedSeats.length === 0) {
            selectedLabelsEl.textContent = 'Belum ada kursi dipilih';
            totalPriceEl.textContent = 'Rp 0';
            selectedIdsInput.value = '';
            submitBtn.disabled = true;
        } else {
            selectedLabelsEl.textContent = selectedSeats.map(function (s) { return s.label; }).join(', ');
            totalPriceEl.textContent = formatRupiah(selectedSeats.length * pricePerSeat);
            selectedIdsInput.value = selectedSeats.map(function (s) { return s.id; }).join(',');
            submitBtn.disabled = false;
        }
    }

    seatButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var seatId = btn.getAttribute('data-seat-id');
            var seatLabel = btn.getAttribute('data-seat-label');
            var idx = selectedSeats.findIndex(function (s) { return s.id === seatId; });

            if (idx > -1) {
                selectedSeats.splice(idx, 1);
                btn.classList.remove('selected');
            } else {
                if (selectedSeats.length >= maxSeats) {
                    alert('Maksimal 6 kursi per pesanan.');
                    return;
                }
                selectedSeats.push({ id: seatId, label: seatLabel });
                btn.classList.add('selected');
            }

            updateSummary();
        });
    });

    updateSummary();
});
