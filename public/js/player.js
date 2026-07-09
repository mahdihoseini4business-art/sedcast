/**
 * Mahdi Podcast CMS — Audio Player Engine
 * Manages the sticky bottom player, queue, and controls
 */
(function($) {
  'use strict';

  window.MpcPlayer = {
    audio:     null,
    queue:     [],
    current:   -1,
    isPlaying: false,

    init: function() {
      this.audio = document.getElementById('mpc-audio-engine');
      if (!this.audio) return;
      this._bindAudioEvents();
      this._bindControls();
      this._bindProgressBar();
      this._buildQueue();
    },

    _buildQueue: function() {
      this.queue = [];
      document.querySelectorAll('.mpc-episode-row').forEach((row, i) => {
        const audio = row.dataset.audio;
        if (audio) {
          this.queue.push({
            id:     row.dataset.epId,
            audio:  audio,
            title:  row.dataset.title,
            cover:  row.dataset.cover,
            season: row.dataset.season,
            el:     row,
          });
        }
      });
    },

    play: function(data) {
      const { audio, title, cover, season, epId } = data;

      // Find index in queue
      const idx = this.queue.findIndex(ep => ep.id === epId || ep.audio === audio);
      if (idx !== -1) this.current = idx;

      this.audio.src = audio;
      this.audio.load();
      this.audio.play();

      this._updatePlayerUI(title, cover, season);
      this._showPlayer();
      this._markActiveRow(epId || audio);
    },

    _updatePlayerUI: function(title, cover, season) {
      document.getElementById('mpc-sticky-title').textContent = title || '—';
      document.getElementById('mpc-sticky-season').textContent = season || '';
      const img = document.getElementById('mpc-sticky-cover-img');
      if (img) img.src = cover || '';
      document.getElementById('mpc-play').querySelector('.mpc-icon-play').classList.add('mpc-icon-pause');
    },

    _showPlayer: function() {
      const player = document.getElementById('mpc-sticky-player');
      player.classList.remove('mpc-hidden');
      player.removeAttribute('aria-hidden');
      document.body.style.paddingBottom = '80px';
    },

    _hidePlayer: function() {
      const player = document.getElementById('mpc-sticky-player');
      player.classList.add('mpc-hidden');
      player.setAttribute('aria-hidden', 'true');
      document.body.style.paddingBottom = '';
      this.audio.pause();
      this.audio.src = '';
    },

    _markActiveRow: function(epId) {
      document.querySelectorAll('.mpc-episode-row').forEach(r => r.classList.remove('mpc-playing'));
      const row = document.querySelector(`.mpc-episode-row[data-ep-id="${epId}"]`);
      if (row) { row.classList.add('mpc-playing'); row.scrollIntoView({behavior:'smooth',block:'nearest'}); }
    },

    _bindAudioEvents: function() {
      const self = this;
      const fill  = document.getElementById('mpc-progress-fill');
      const thumb = document.getElementById('mpc-progress-thumb');
      const curr  = document.getElementById('mpc-time-current');
      const total = document.getElementById('mpc-time-total');
      const play  = document.getElementById('mpc-play');

      this.audio.addEventListener('timeupdate', function() {
        if (!self.audio.duration) return;
        const pct = (self.audio.currentTime / self.audio.duration) * 100;
        if (fill)  fill.style.width = pct + '%';
        if (thumb) thumb.style.left = pct + '%';
        if (curr)  curr.textContent = self._fmtTime(self.audio.currentTime);
      });

      this.audio.addEventListener('loadedmetadata', function() {
        if (total) total.textContent = self._fmtTime(self.audio.duration);
      });

      this.audio.addEventListener('play',  function() {
        if (play) {
          const icon = play.querySelector('.mpc-icon-play');
          if (icon) icon.classList.add('mpc-icon-pause');
        }
        self.isPlaying = true;
      });
      this.audio.addEventListener('pause', function() {
        if (play) {
          const icon = play.querySelector('.mpc-icon-play');
          if (icon) icon.classList.remove('mpc-icon-pause');
        }
        self.isPlaying = false;
      });
      this.audio.addEventListener('ended', function() { self.playNext(); });

      this.audio.addEventListener('error', function() {
        console.warn('[MPC] Audio error:', self.audio.error);
      });
    },

    _bindControls: function() {
      const self = this;

      // Play/Pause
      document.getElementById('mpc-play')?.addEventListener('click', function() {
        if (self.audio.paused) self.audio.play(); else self.audio.pause();
      });

      // Prev / Next
      document.getElementById('mpc-prev')?.addEventListener('click', function() { self.playPrev(); });
      document.getElementById('mpc-next')?.addEventListener('click', function() { self.playNext(); });

      // Rewind / Forward 15s
      document.getElementById('mpc-rw')?.addEventListener('click', function() {
        self.audio.currentTime = Math.max(0, self.audio.currentTime - 15);
      });
      document.getElementById('mpc-fw')?.addEventListener('click', function() {
        self.audio.currentTime = Math.min(self.audio.duration || 0, self.audio.currentTime + 15);
      });

      // Speed
      document.getElementById('mpc-speed')?.addEventListener('change', function() {
        self.audio.playbackRate = parseFloat(this.value);
      });

      // Volume
      document.getElementById('mpc-volume')?.addEventListener('input', function() {
        self.audio.volume = parseFloat(this.value);
        const icon = document.getElementById('mpc-mute')?.querySelector('.mpc-icon-volume');
        if (icon) {
          icon.classList.toggle('mpc-icon-muted', self.audio.volume === 0);
        }
      });

      // Mute toggle
      document.getElementById('mpc-mute')?.addEventListener('click', function() {
        self.audio.muted = !self.audio.muted;
        const icon = this.querySelector('.mpc-icon-volume');
        if (icon) {
          icon.classList.toggle('mpc-icon-muted', self.audio.muted);
        }
      });

      // Close player
      document.getElementById('mpc-close-player')?.addEventListener('click', function() {
        self._hidePlayer();
        document.querySelectorAll('.mpc-episode-row').forEach(r => r.classList.remove('mpc-playing'));
      });
    },

    _bindProgressBar: function() {
      const self = this;
      const bar  = document.getElementById('mpc-progress-bar');
      if (!bar) return;

      function seek(e) {
        if (!self.audio.duration) return;
        const rect = bar.getBoundingClientRect();
        // RTL: right side is start
        let x = e.touches ? e.touches[0].clientX : e.clientX;
        const pct = 1 - Math.max(0, Math.min(1, (x - rect.left) / rect.width));
        self.audio.currentTime = pct * self.audio.duration;
      }

      let dragging = false;
      bar.addEventListener('mousedown', function(e) { dragging = true; seek(e); });
      document.addEventListener('mousemove', function(e) { if (dragging) seek(e); });
      document.addEventListener('mouseup',   function()  { dragging = false; });
      bar.addEventListener('touchstart', seek, {passive:true});
      bar.addEventListener('touchmove',  seek, {passive:true});

      // Keyboard
      bar.addEventListener('keydown', function(e) {
        if (!self.audio.duration) return;
        if (e.key === 'ArrowLeft')  self.audio.currentTime = Math.min(self.audio.duration, self.audio.currentTime + 5);
        if (e.key === 'ArrowRight') self.audio.currentTime = Math.max(0, self.audio.currentTime - 5);
      });
    },

    playNext: function() {
      if (this.current < this.queue.length - 1) {
        this.current++;
        const ep = this.queue[this.current];
        this.play({ audio: ep.audio, title: ep.title, cover: ep.cover, season: ep.season, epId: ep.id });
      }
    },

    playPrev: function() {
      if (this.audio.currentTime > 3) { this.audio.currentTime = 0; return; }
      if (this.current > 0) {
        this.current--;
        const ep = this.queue[this.current];
        this.play({ audio: ep.audio, title: ep.title, cover: ep.cover, season: ep.season, epId: ep.id });
      }
    },

    _fmtTime: function(s) {
      if (!s || isNaN(s)) return '0:00';
      const h = Math.floor(s / 3600);
      const m = Math.floor((s % 3600) / 60);
      const sec = Math.floor(s % 60);
      if (h > 0) return h + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
      return m + ':' + String(sec).padStart(2,'0');
    },
  };

})(jQuery);
