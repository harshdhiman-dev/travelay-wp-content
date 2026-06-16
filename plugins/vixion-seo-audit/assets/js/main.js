/* Vixion SEO Audit v3.0 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    // FAQ toggles
    document.querySelectorAll('.vx-faq-q').forEach(function (q) {
      q.addEventListener('click', function () {
        this.parentElement.classList.toggle('open');
      });
    });
  });
})();
