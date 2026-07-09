/**
 * Mahdi Podcast CMS — Frontend Interactions
 * Accordion, play buttons, search/filter, read more, lazy load
 */
(function($) {
  'use strict';

  $(document).ready(function() {

    // Init player engine
    if (window.MpcPlayer) MpcPlayer.init();

    // ── Play Buttons ──────────────────────────────────────────
    $(document).on('click', '.mpc-play-episode', function(e) {
      e.preventDefault();
      const $btn = $(this);
      MpcPlayer.play({
        audio:  $btn.data('audio'),
        title:  $btn.data('title'),
        cover:  $btn.data('cover'),
        season: $btn.data('season'),
        epId:   String($btn.data('ep-id')),
      });
    });

    // ── Season Accordion ──────────────────────────────────────
    $(document).on('click keydown', '.mpc-season-header', function(e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      const $season = $(this).closest('.mpc-season');
      const $body   = $season.find('.mpc-season-body').first();
      const isOpen  = $season.hasClass('mpc-open');

      $season.toggleClass('mpc-open');
      $(this).attr('aria-expanded', !isOpen);

      if (isOpen) {
        $body.slideUp(280);
      } else {
        $body.slideDown(280);
      }
    });

    // ── Read More ─────────────────────────────────────────────
    $(document).on('click', '.mpc-read-more-btn', function() {
      const $btn  = $(this);
      const $full = $btn.next('.mpc-ep-full-desc');
      const $ep   = $btn.closest('.mpc-episode-row').find('.mpc-ep-excerpt');
      const exp   = $btn.attr('aria-expanded') === 'true';

      if (exp) {
        $full.addClass('mpc-hidden');
        $ep.show();
        $btn.attr('aria-expanded', 'false').find('.mpc-read-more-text').text('مشاهده بیشتر ↓');
      } else {
        $full.removeClass('mpc-hidden');
        $ep.hide();
        $btn.attr('aria-expanded', 'true').find('.mpc-read-more-text').text('بستن ↑');
      }
    });

    // ── Search & Filter ───────────────────────────────────────
    let searchTimeout;

    function triggerSearch() {
      const query  = $('#mpc-search-input').val()  || '';
      const season = $('#mpc-season-filter').val() || '';
      const sort   = $('#mpc-sort-select').val()   || 'newest';
      const isFiltering = query.length > 0 || season !== '' || sort !== 'newest';

      if (!isFiltering) {
        // Restore original DOM
        resetSearch();
        return;
      }

      $.ajax({
        url:  MPC.ajaxUrl,
        type: 'POST',
        data: {
          action: 'mpc_search',
          nonce:  MPC.nonce,
          query:  query,
          season: season,
          sort:   sort,
        },
        success: function(res) {
          if (!res.success) return;
          if (res.data.count === 0) {
            $('#mpc-no-results').removeClass('mpc-hidden');
            $('.mpc-podcast-wrapper').hide();
          } else {
            $('#mpc-no-results').addClass('mpc-hidden');
            renderSearchResults(res.data.html);
          }
        },
        error: function() {
          console.warn('[MPC] Search failed');
        }
      });
    }

    function renderSearchResults(html) {
      let $wrapper = $('#mpc-search-results-wrapper');
      if (!$wrapper.length) {
        $wrapper = $('<div id="mpc-search-results-wrapper" class="mpc-episodes-list mpc-flat"></div>');
        $('.mpc-podcast-wrapper').after($wrapper);
      }
      $wrapper.html(html).show();
      $('.mpc-podcast-wrapper').hide();
      $wrapper.find('.mpc-ep-cover img').each(lazyLoadImg);
      if (window.MpcPlayer) MpcPlayer._buildQueue();
    }

    function resetSearch() {
      $('#mpc-search-results-wrapper').hide();
      $('.mpc-podcast-wrapper').show();
      $('#mpc-no-results').addClass('mpc-hidden');
      if (window.MpcPlayer) MpcPlayer._buildQueue();
    }

    $('#mpc-search-input').on('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(triggerSearch, 380);
    });

    $('#mpc-season-filter, #mpc-sort-select').on('change', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(triggerSearch, 100);
    });

    // ── Lazy Load Images ──────────────────────────────────────
    function lazyLoadImg() {
      const img = this;
      if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver(function(entries, o) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              const el = entry.target;
              if (el.dataset.src) el.src = el.dataset.src;
              o.unobserve(el);
            }
          });
        }, { rootMargin: '200px' });
        obs.observe(img);
      }
    }

    // Convert eager-loaded imgs to lazy
    $('.mpc-ep-cover, .mpc-season-cover img').each(function() {
      const $img = $(this);
      if (!$img.attr('loading')) $img.attr('loading', 'lazy');
    });

    // ── Keyboard shortcut: Space to play/pause ────────────────
    $(document).on('keydown', function(e) {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
      if (e.key === ' ') {
        e.preventDefault();
        const audio = document.getElementById('mpc-audio-engine');
        if (audio && audio.src) {
          audio.paused ? audio.play() : audio.pause();
        }
      }
    });

  });

})(jQuery);
