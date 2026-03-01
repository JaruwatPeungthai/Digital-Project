// Modal Popup Utility Functions
function showModal(message, type = 'info', title = 'แจ้งเตือน') {
  // Create modal elements if they don't exist
  let overlay = document.getElementById('modalOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'modalOverlay';
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal-popup" id="modalPopup">
        <div class="modal-title" id="modalTitle">แจ้งเตือน</div>
        <div class="modal-message" id="modalMessage"></div>
        <div class="modal-buttons">
          <button class="modal-btn modal-btn-ok" onclick="closeModal()">ตกลง</button>
          <button class="modal-btn modal-btn-cancel" id="modalCancelBtn" onclick="closeModal()" style="display: none;">ยกเลิก</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  const popup = document.getElementById('modalPopup');
  const titleEl = document.getElementById('modalTitle');
  const messageEl = document.getElementById('modalMessage');

  titleEl.textContent = title;
  messageEl.textContent = message;
  
  // Set popup style based on type
  popup.className = `modal-popup ${type}`;
  
  overlay.classList.add('active');
}

function closeModal() {
  const overlay = document.getElementById('modalOverlay');
  if (overlay) {
    overlay.classList.remove('active');
  }
}

function showConfirmModal(message, onConfirm, title = 'ยืนยันการดำเนินการ') {
  let overlay = document.getElementById('modalOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'modalOverlay';
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal-popup" id="modalPopup">
        <div class="modal-title" id="modalTitle">ยืนยัน</div>
        <div class="modal-message" id="modalMessage"></div>
        <div class="modal-buttons">
          <button class="modal-btn modal-btn-ok" id="modalConfirmBtn" onclick="confirmModalAction()">ตกลง</button>
          <button class="modal-btn modal-btn-cancel" onclick="closeModal()">ยกเลิก</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  window.currentConfirmCallback = onConfirm;

  const popup = document.getElementById('modalPopup');
  const titleEl = document.getElementById('modalTitle');
  const messageEl = document.getElementById('modalMessage');

  titleEl.textContent = title;
  messageEl.textContent = message;
  popup.className = 'modal-popup warning';
  
  overlay.classList.add('active');
}

function confirmModalAction() {
  if (typeof window.currentConfirmCallback === 'function') {
    window.currentConfirmCallback();
  }
  closeModal();
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('click', function(e) {
    const overlay = document.getElementById('modalOverlay');
    if (overlay && e.target === overlay) {
      closeModal();
    }
  });
});
