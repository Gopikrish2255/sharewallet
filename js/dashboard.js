// Function to accept a request
function acceptRequest(linkedUserId) {
    fetch('account_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            linkedUserId: linkedUserId,
            action: 'accept'
        })
    }).then(response => {
        if (response.ok) {
            // Handle successful acceptance (e.g., remove from UI)
            alert('Request accepted!');
            location.reload();
        } else {
            alert('Failed to accept the request.');
        }
    });
}

// Function to decline a request
function declineRequest(linkedUserId) {
    fetch('account_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            linkedUserId: linkedUserId,
            action: 'decline'
        })
    }).then(response => {
        if (response.ok) {
            // Handle successful decline (e.g., remove from UI)
            alert('Request declined!');
            location.reload();
        } else {
            alert('Failed to decline the request.');
        }
    });
}

// Event listeners for accept and decline buttons
document.querySelectorAll('.accept-button').forEach(button => {
    button.addEventListener('click', () => {
        const linkedUserId = button.getAttribute('data-linked-user-id');
        acceptRequest(linkedUserId);
    });
});

document.querySelectorAll('.decline-button').forEach(button => {
    button.addEventListener('click', () => {
        const linkedUserId = button.getAttribute('data-linked-user-id');
        declineRequest(linkedUserId);
    });
});
// Function to accept a request
function acceptRequest(linkedUserId) {
    fetch('account_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'accept', linkedUserId: linkedUserId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request accepted successfully!');
            location.reload(); // Refresh the page to see changes
        } else {
            alert('Failed to accept the request. Please try again.');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to decline a request
function declineRequest(linkedUserId) {
    fetch('account_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'decline', linkedUserId: linkedUserId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request declined successfully!');
            location.reload(); // Refresh the page to see changes
        } else {
            alert('Failed to decline the request. Please try again.');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Attach event listeners to buttons (assuming buttons have data attributes for linked user IDs)
document.querySelectorAll('.accept-button').forEach(button => {
    button.addEventListener('click', function() {
        const linkedUserId = this.getAttribute('data-linked-user-id');
        acceptRequest(linkedUserId);
    });
});

document.querySelectorAll('.decline-button').forEach(button => {
    button.addEventListener('click', function() {
        const linkedUserId = this.getAttribute('data-linked-user-id');
        declineRequest(linkedUserId);
    });
});
