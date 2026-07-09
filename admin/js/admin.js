/**
 * Mahdi Podcast CMS — Admin Scripts
 * Media uploader, audio picker
 */
(function($) {
  'use strict';

  $(document).ready(function() {

    // ── Generic Image Uploader ────────────────────────────────
    $(document).on('click', '.mpc-upload-btn', function(e) {
      e.preventDefault();
      const targetId = $(this).data('target');
      const $target  = $('#' + targetId);
      const $preview = $('#' + targetId + '_preview');

      const frame = wp.media({
        title:    'انتخاب تصویر',
        button:   { text: 'استفاده از این تصویر' },
        multiple: false,
        library:  { type: 'image' },
      });

      frame.on('select', function() {
        const attachment = frame.state().get('selection').first().toJSON();
        $target.val(attachment.url);
        $preview.html('<img src="' + attachment.url + '" style="max-width:200px;margin-top:8px;border-radius:6px;">');
      });

      frame.open();
    });

    // ── Audio File Uploader ───────────────────────────────────
    $(document).on('click', '.mpc-upload-audio-btn', function(e) {
      e.preventDefault();
      const targetId = $(this).data('target');
      const $target  = $('#' + targetId);

      const frame = wp.media({
        title:    'انتخاب فایل صوتی',
        button:   { text: 'استفاده از این فایل' },
        multiple: false,
        library:  { type: 'audio' },
      });

      frame.on('select', function() {
        const attachment = frame.state().get('selection').first().toJSON();
        $target.val(attachment.url);

        // Try to get duration from attachment metadata
        if (attachment.fileLength) {
          $('#_mpc_duration').val(attachment.fileLength);
        }
        if (attachment.filesizeInBytes) {
          $('#_mpc_file_size').val(attachment.filesizeInBytes);
        }

        // Show preview
        let $preview = $('.mpc-audio-preview');
        if (!$preview.length) {
          $preview = $('<div class="mpc-audio-preview"></div>');
          $target.closest('.mpc-audio-field').after($preview);
        }
        $preview.html('<audio controls src="' + attachment.url + '" style="width:100%;margin-top:8px;"></audio>');
      });

      frame.open();
    });

    // ── Column: Audio indicator in episode list ───────────────
    // Handled server-side via columns filter

  });

})(jQuery);
