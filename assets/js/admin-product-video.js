/* global wp, jQuery */
(function ($) {
  "use strict";

  function initPrimefitProductVideoField() {
    const $field = $(".primefit-product-video-field");
    if (!$field.length) return;

    const $input = $field.find("[data-primefit-product-video-id]");
    const $preview = $field.find("[data-primefit-product-video-preview]");
    const $selectBtn = $field.find("[data-primefit-product-video-select]");
    const $removeBtn = $field.find("[data-primefit-product-video-remove]");

    const $thumbInput = $field.find("[data-primefit-product-video-thumb-id]");
    const $thumbPreview = $field.find("[data-primefit-product-video-thumb-preview]");
    const $thumbSelectBtn = $field.find("[data-primefit-product-video-thumb-select]");
    const $thumbRemoveBtn = $field.find("[data-primefit-product-video-thumb-remove]");

    let frame = null;
    let thumbFrame = null;

    function setVideo(attachment) {
      const id = attachment.id || 0;
      const url =
        (attachment.url ||
          (attachment.attributes && attachment.attributes.url) ||
          "") + "";

      $input.val(id);

      if (url) {
        $preview.html(
          [
            '<video controls preload="metadata" style="max-width:100%; height:auto;">',
            '<source src="' + url.replace(/"/g, "&quot;") + '"/>',
            "</video>",
            '<p style="margin: 8px 0 0;">',
            '<a href="' +
              url.replace(/"/g, "&quot;") +
              '" target="_blank" rel="noopener noreferrer">Open video</a>',
            "</p>",
          ].join("")
        );
        $removeBtn.show();
      } else {
        $preview.html("<p style=\"margin:0;\">No video selected.</p>");
        $removeBtn.hide();
      }
    }

    $selectBtn.on("click", function (e) {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: "Select/Upload Product Video",
        library: { type: "video" },
        button: { text: "Use this video" },
        multiple: false,
      });

      frame.on("select", function () {
        const selection = frame.state().get("selection");
        const attachment = selection.first().toJSON();
        setVideo(attachment);
      });

      frame.open();
    });

    $removeBtn.on("click", function (e) {
      e.preventDefault();
      $input.val("");
      $preview.html("<p style=\"margin:0;\">No video selected.</p>");
      $removeBtn.hide();
    });

    function setThumb(attachment) {
      const id = attachment.id || 0;
      const url =
        (attachment.url ||
          (attachment.attributes && attachment.attributes.url) ||
          "") + "";

      $thumbInput.val(id);

      if (url) {
        $thumbPreview.html(
          '<img src="' +
            url.replace(/"/g, "&quot;") +
            '" alt="" style="max-width:100%; height:auto; display:block;" />'
        );
        $thumbRemoveBtn.show();
      } else {
        $thumbPreview.html("<p style=\"margin:0;\">No thumbnail selected.</p>");
        $thumbRemoveBtn.hide();
      }
    }

    $thumbSelectBtn.on("click", function (e) {
      e.preventDefault();

      if (thumbFrame) {
        thumbFrame.open();
        return;
      }

      thumbFrame = wp.media({
        title: "Select/Upload Video Thumbnail",
        library: { type: "image" },
        button: { text: "Use this thumbnail" },
        multiple: false,
      });

      thumbFrame.on("select", function () {
        const selection = thumbFrame.state().get("selection");
        const attachment = selection.first().toJSON();
        setThumb(attachment);
      });

      thumbFrame.open();
    });

    $thumbRemoveBtn.on("click", function (e) {
      e.preventDefault();
      $thumbInput.val("");
      $thumbPreview.html("<p style=\"margin:0;\">No thumbnail selected.</p>");
      $thumbRemoveBtn.hide();
    });
  }

  $(document).ready(initPrimefitProductVideoField);
})(jQuery);

