document.getElementById('addCategoryForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'add');

    fetch('manage_categories.php', {
        method: 'POST',
        body: formData
    }).then(response => response.text())
      .then(data => {
          location.reload();
      });
});
