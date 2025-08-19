document.querySelectorAll('.send-link-request-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const userId = this.getAttribute('data-id');
        const formData = new FormData();
        formData.append('linkUserId', userId);

        fetch('linked_accounts.php', {
            method: 'POST',
            body: formData
        }).then(response => response.text())
          .then(data => {
              // Optionally update UI or reload the page after sending the request
              location.reload();
          });
    });
});
