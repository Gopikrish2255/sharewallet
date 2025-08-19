// Function to link an account
function linkAccount(linkedUserId) {
    fetch('linking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'link', linkedUserId: linkedUserId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request sent successfully!');
            location.reload(); // Refresh to see changes
        } else {
            alert('Failed to send request. This user may already be linked.');
        }
    })
    .catch(error => console.error('Error:', error));
}
