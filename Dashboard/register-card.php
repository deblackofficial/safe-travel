<?php
include 'conn.php'; // Changed from ../conn.php to conn.php for consistency
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register RFID Card</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .container { max-width: 600px; margin-top: 30px; }
    .card { border-radius: 15px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
    .card-header { border-radius: 15px 15px 0 0 !important; }
    .status-box { transition: all 0.3s ease; }
    .user-avatar { width: 80px; height: 80px; background: #0d6efd; color: white; font-size: 2rem; }
    .scan-animation {
      animation: pulse 2s infinite;
      border: 2px solid #0d6efd;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
      70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
      100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }
    #cardInfo { transition: all 0.3s ease; }
    .debug-info {
      position: fixed;
      top: 10px;
      right: 10px;
      background: rgba(0,0,0,0.8);
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-size: 12px;
      max-width: 300px;
      z-index: 1000;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-credit-card"></i> Assign RFID Card to User</h4>
      </div>
      <div class="card-body">
        <form id="userForm">
          <div class="input-group mb-4">
            <input type="number" name="user_id" id="user_id" class="form-control form-control-lg" placeholder="Enter User ID" required>
            <button class="btn btn-primary btn-lg" type="submit">
              <i class="bi bi-search"></i> Find User
            </button>
          </div>
        </form>

        <div id="userDetails" class="text-center" style="display:none;">
          <div class="user-avatar d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
            <i class="bi bi-person"></i>
          </div>
          
          <h4 id="name" class="mb-3"></h4>
          
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="d-flex align-items-center justify-content-center mb-2">
                <i class="bi bi-person-badge me-2"></i>
                <span id="username"></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex align-items-center justify-content-center mb-2">
                <i class="bi bi-envelope me-2"></i>
                <span id="email"></span>
              </div>
            </div>
          </div>

          <div class="scan-section mt-4 p-4 rounded text-center scan-animation">
            <i class="bi bi-credit-card fs-1 text-primary"></i>
            <h5 class="mt-2">Scan RFID Card Now</h5>
            <p class="text-muted">Place the card near the reader</p>
          </div>

          <div class="status-box alert alert-info mt-4">
            <i class="bi bi-hourglass-split"></i> Waiting for card scan...
          </div>

          <div id="cardInfo" class="mt-3" style="display:none;">
            <div class="card bg-light p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <span>Card UID:</span>
                <strong id="cardUid"></strong>
              </div>
            </div>
            <button id="saveBtn" class="btn btn-success btn-lg w-100">
              <i class="bi bi-save"></i> Assign Card to User
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Debug info (remove in production) -->
  <div id="debugInfo" class="debug-info" style="display:none;">
    <div>Last check: <span id="lastCheck">-</span></div>
    <div>Status: <span id="debugStatus">-</span></div>
    <div>Response: <span id="debugResponse">-</span></div>
  </div>

  <script>
    let currentUserId = null;
    let currentCardUid = null;
    let isChecking = false;
    let checkInterval = null;
    let checkCount = 0;
    let userHasCard = false;

    // Show debug info (comment this line in production)
    document.getElementById('debugInfo').style.display = 'block';

    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const userId = document.getElementById('user_id').value;

        // Show loading state
        showAlert('Looking for user...', 'info');

        fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            updateDebug('User fetch response', response.status);
            
            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            updateDebug('User data', JSON.stringify(data));
            
            if (data.status === 'ok') {
                currentUserId = data.user.id;
                userHasCard = data.has_card || false;
                document.getElementById('userDetails').style.display = 'block';
                
                // Update user info
                document.getElementById('name').textContent = `${data.user.first_name} ${data.user.last_name}`;
                document.getElementById('username').textContent = data.user.username;
                document.getElementById('email').textContent = data.user.email;
                
                // Check if user already has a card
                if (data.has_card && data.existing_card) {
                    // User already has a card - show existing card info and disable assignment
                    showAlert(`⚠️ User already has a card assigned: ${data.existing_card.card_uid}`, 'warning');
                    
                    // Update scan section to show user already has card
                    const scanSection = document.querySelector('.scan-section');
                    scanSection.classList.remove('scan-animation');
                    scanSection.innerHTML = `
                        <i class="bi bi-person-check fs-1 text-warning"></i>
                        <h5 class="mt-2">User Already Has Card</h5>
                        <p class="text-muted">Card UID: ${data.existing_card.card_uid}</p>
                    `;
                    
                    // Show existing card info instead of scan options
                    document.getElementById('cardInfo').style.display = 'block';
                    document.getElementById('cardInfo').innerHTML = `
                        <div class="card bg-info p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Assigned Card:</span>
                                <strong>${data.existing_card.card_uid}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Assigned Date:</span>
                                <strong>${new Date(data.existing_card.assigned_at).toLocaleString()}</strong>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This user already has a card assigned.
                        </div>
                        <button class="btn btn-secondary btn-lg w-100" onclick="resetForm()">
                            <i class="bi bi-arrow-left"></i> Check Another User
                        </button>
                    `;
                } else {
                    // User doesn't have a card - proceed with normal flow
                    startCardCheck();
                    showAlert('User found! Now scan the RFID card', 'success');
                }
            } else {
                showAlert(data.message || 'User not found', 'danger');
            }
        })
        .catch(error => {
            updateDebug('User fetch error', error.message);
            showAlert('Error fetching user data: ' + error.message, 'danger');
            console.error('Error:', error);
        });
    });

    function startCardCheck() {
        // Clear any existing interval
        if (checkInterval) {
            clearInterval(checkInterval);
        }
        
        checkCount = 0;
        showAlert('Waiting for card scan...', 'info');
        
        // Start new checking interval
        checkInterval = setInterval(() => {
            if (!isChecking) {
                checkForCard();
            }
        }, 1000); // Check every second
    }

    function checkForCard() {
        if (isChecking) return;
        
        isChecking = true;
        checkCount++;
        
        // Add timestamp to prevent caching
        const timestamp = Date.now();
        const url = `check_temp_card.php?t=${timestamp}&check=${checkCount}`;
        
        updateDebug('Checking for card', `Attempt ${checkCount}`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        })
        .then(response => {
            updateDebug('Card check response', `Status: ${response.status}`);
            
            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    updateDebug('Non-JSON response', text.substring(0, 100));
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                });
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            updateDebug('Card data', JSON.stringify(data));
            
            if (data.status === 'available' && data.uid) {
                // Card available for assignment
                currentCardUid = data.uid;
                displayCardInfo(data.uid, 'available');
                clearInterval(checkInterval);
                updateDebug('Card available', data.uid);
                
            } else if (data.status === 'registered' && data.uid) {
                // Card already registered
                currentCardUid = data.uid;
                displayCardInfo(data.uid, 'registered', data.user_info);
                clearInterval(checkInterval);
                updateDebug('Card registered', data.uid);
                
            } else if (data.status === 'error') {
                showAlert('Error: ' + (data.message || 'Unknown error'), 'danger');
                updateDebug('Error', data.message);
            } else {
                // Status is 'empty', continue checking
                updateDebug('No card', 'Continue checking');
            }
        })
        .catch(error => {
            updateDebug('Card check error', error.message);
            console.error('Error checking for card:', error);
            
            // Don't show alert for every check error, just log it
            if (checkCount % 10 === 0) { // Show error every 10 attempts
                showAlert('Connection issue - retrying...', 'warning');
            }
        })
        .finally(() => {
            isChecking = false;
        });
    }

    function displayCardInfo(uid, status = 'available', userInfo = null) {
        // Update UI elements
        document.getElementById('cardUid').textContent = uid;
        document.getElementById('cardInfo').style.display = 'block';
        
        const scanSection = document.querySelector('.scan-section');
        scanSection.classList.remove('scan-animation');
        
        if (status === 'registered' && userInfo) {
            // Card is already registered to someone else
            showAlert(`⚠️ Card already registered to: ${userInfo.name}`, 'warning');
            
            // Update scan section to show registered user
            scanSection.innerHTML = `
                <i class="bi bi-person-check fs-1 text-warning"></i>
                <h5 class="mt-2">Card Already Registered</h5>
                <p class="text-muted">UID: ${uid}</p>
            `;
            
            // Update card info to show current owner instead of save button
            document.getElementById('cardInfo').innerHTML = `
                <div class="card bg-warning p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Card UID:</span>
                        <strong>${uid}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Registered to:</span>
                        <strong>${userInfo.name}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Username:</span>
                        <strong>${userInfo.username}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Email:</span>
                        <strong>${userInfo.email}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Assigned:</span>
                        <strong>${new Date(userInfo.assigned_at).toLocaleString()}</strong>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> This card is already assigned to another user.
                </div>
                <button class="btn btn-secondary btn-lg w-100" onclick="resetForm()">
                    <i class="bi bi-arrow-left"></i> Scan Another Card
                </button>
            `;
            
        } else {
            // Card is available for assignment
            showAlert(`✅ Card available: ${uid}`, 'success');
            
            scanSection.innerHTML = `
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <h5 class="mt-2">Card Ready</h5>
                <p class="text-muted">UID: ${uid}</p>
            `;
            
            // Only enable save button if user doesn't have a card already
            if (!userHasCard) {
                const saveBtn = document.getElementById('saveBtn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                }
            } else {
                // User already has a card, so don't allow assignment
                document.getElementById('cardInfo').innerHTML = `
                    <div class="card bg-light p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Card UID:</span>
                            <strong>${uid}</strong>
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> This user already has a card assigned.
                    </div>
                    <button class="btn btn-secondary btn-lg w-100" onclick="resetForm()">
                        <i class="bi bi-arrow-left"></i> Check Another User
                    </button>
                `;
            }
        }
    }

    document.getElementById('saveBtn').addEventListener('click', function() {
        if (!currentUserId || !currentCardUid) {
            showAlert('Missing user or card information', 'danger');
            return;
        }
        
        if (userHasCard) {
            showAlert('User already has a card assigned', 'warning');
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Assigning...';
        
        const formData = new FormData();
        formData.append('user_id', currentUserId);
        formData.append('card_uid', currentCardUid);
        
        fetch('assign_card.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            updateDebug('Assignment result', JSON.stringify(data));
            
            if (data.status === 'success') {
                showAlert('✅ ' + (data.message || 'Card assigned successfully'), 'success');
                resetForm();
            } else {
                throw new Error(data.message || 'Failed to assign card');
            }
        })
        .catch(error => {
            updateDebug('Assignment error', error.message);
            showAlert('❌ ' + error.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Assign Card to User';
        });
    });

    function resetForm() {
        setTimeout(() => {
            document.getElementById('userForm').reset();
            document.getElementById('userDetails').style.display = 'none';
            document.getElementById('cardInfo').style.display = 'none';
            
            const btn = document.getElementById('saveBtn');
            btn.innerHTML = '<i class="bi bi-save"></i> Assign Card to User';
            btn.disabled = false;
            
            // Reset scan section
            const scanSection = document.querySelector('.scan-section');
            scanSection.classList.add('scan-animation');
            scanSection.innerHTML = `
                <i class="bi bi-credit-card fs-1 text-primary"></i>
                <h5 class="mt-2">Scan RFID Card Now</h5>
                <p class="text-muted">Place the card near the reader</p>
            `;
            
            showAlert('Ready for next card assignment', 'info');
            currentUserId = null;
            currentCardUid = null;
            userHasCard = false;
            checkCount = 0;
        }, 3000);
    }

    function showAlert(message, type) {
        const statusBox = document.querySelector('.status-box');
        statusBox.className = `status-box alert alert-${type}`;
        
        // Add appropriate icon based on type
        let icon = '';
        switch(type) {
            case 'success': icon = 'bi-check-circle'; break;
            case 'danger': icon = 'bi-exclamation-triangle'; break;
            case 'warning': icon = 'bi-exclamation-circle'; break;
            default: icon = 'bi-info-circle';
        }
        
        statusBox.innerHTML = `<i class="bi ${icon}"></i> ${message}`;
    }

    function updateDebug(label, value) {
        const now = new Date().toLocaleTimeString();
        document.getElementById('lastCheck').textContent = now;
        document.getElementById('debugStatus').textContent = label;
        document.getElementById('debugResponse').textContent = value;
    }

    // Clear intervals when page unloads
    window.addEventListener('beforeunload', function() {
        if (checkInterval) {
            clearInterval(checkInterval);
        }
    });
  </script>
</body>
</html>