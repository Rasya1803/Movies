document.addEventListener('DOMContentLoaded', function () {
    // 1. Toggle Dark Mode dengan localStorage
    var themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var html = document.documentElement;
            var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('cineverse_theme', next);
        });
    }

    // 2. Live Search & Filter di Halaman Katalog Film (fetch + JSON)
    var searchInput = document.getElementById('liveSearch');
    var genreFilter = document.getElementById('genreFilter');
    var statusFilter = document.getElementById('statusFilter');
    var catalogGrid = document.getElementById('catalogGrid');

    function renderSkeletons() {
        var items = [];
        for (var i = 0; i < 4; i++) {
            items.push(
                '<div class="skeleton-card" aria-hidden="true">' +
                    '<div class="skeleton-poster"></div>' +
                    '<div class="movie-body">' +
                        '<div class="skeleton-line" style="width:75%;"></div>' +
                        '<div class="skeleton-line" style="width:50%;"></div>' +
                    '</div>' +
                '</div>'
            );
        }
        catalogGrid.innerHTML = items.join('');
    }

    function fetchMovies() {
        if (!catalogGrid) return;
        var q = searchInput ? searchInput.value.trim() : '';
        var genre = genreFilter ? genreFilter.value : '';
        var status = statusFilter ? statusFilter.value : '';
        var apiUrl = catalogGrid.getAttribute('data-api-url');

        renderSkeletons();

        var params = new URLSearchParams({ q: q, genre: genre, status: status });
        fetch(apiUrl + '?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (movies) {
                if (!movies.length) {
                    catalogGrid.innerHTML =
                        '<div class="card" style="grid-column:1/-1;text-align:center;">' +
                            '<p class="text-muted" style="margin-bottom:14px;">Tidak ada film yang cocok dengan filter pencarian Anda.</p>' +
                            '<button type="button" id="resetFilterBtn" class="btn btn-primary btn-sm">Reset Filter Pencarian</button>' +
                        '</div>';
                    var resetBtn = document.getElementById('resetFilterBtn');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', function () {
                            if (searchInput) searchInput.value = '';
                            if (genreFilter) genreFilter.value = '';
                            if (statusFilter) statusFilter.value = '';
                            fetchMovies();
                        });
                    }
                    return;
                }
                catalogGrid.innerHTML = movies.map(function (m) {
                    return (
                        '<article class="movie-card">' +
                            '<a href="movie.php?id=' + m.id + '" class="movie-poster-wrap">' +
                                '<img src="' + m.poster_url + '" alt="' + m.title + '" class="movie-poster">' +
                                '<span class="age-badge">' + m.age_rating + '</span>' +
                            '</a>' +
                            '<div class="movie-body">' +
                                '<h3 class="movie-title"><a href="movie.php?id=' + m.id + '">' + m.title + '</a></h3>' +
                                '<div class="movie-meta">' +
                                    '<span>' + (m.genres || '-') + '</span>' +
                                    '<span class="mono">' + m.duration_min + 'm</span>' +
                                '</div>' +
                                '<a href="movie.php?id=' + m.id + '" class="btn btn-primary btn-sm" style="margin-top:auto;">Lihat Jadwal</a>' +
                            '</div>' +
                        '</article>'
                    );
                }).join('');
            })
            .catch(function (err) {
                console.error(err);
            });
    }

    if (searchInput) searchInput.addEventListener('input', fetchMovies);
    if (genreFilter) genreFilter.addEventListener('change', fetchMovies);
    if (statusFilter) statusFilter.addEventListener('change', fetchMovies);
});
