// --- Custom Modals Logic ---
function customPrompt(message, defaultValue = '') {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-prompt-modal');
        if (!modal) return resolve(prompt(message, defaultValue)); // fallback

        const msgEl = document.getElementById('custom-prompt-message');
        const inputEl = document.getElementById('custom-prompt-input');
        const btnOk = document.getElementById('custom-prompt-ok');
        const btnCancel = document.getElementById('custom-prompt-cancel');

        msgEl.textContent = message;
        inputEl.value = defaultValue;
        modal.style.display = 'flex';
        inputEl.focus();

        const handleKey = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnOk.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                btnCancel.click();
            }
        };
        window.addEventListener('keydown', handleKey);

        const cleanup = () => {
            modal.style.display = 'none';
            btnOk.onclick = null;
            btnCancel.onclick = null;
            window.removeEventListener('keydown', handleKey);
        };

        btnOk.onclick = () => { resolve(inputEl.value); cleanup(); };
        btnCancel.onclick = () => { resolve(null); cleanup(); };
    });
}

function customAlert(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-alert-modal');
        if (!modal) { alert(message); return resolve(); } // fallback

        const msgEl = document.getElementById('custom-alert-message');
        const btnOk = document.getElementById('custom-alert-ok');

        msgEl.textContent = message;
        modal.style.display = 'flex';
        
        const handleKey = (e) => {
            if (e.key === 'Enter' || e.key === 'Escape') {
                e.preventDefault();
                btnOk.click();
            }
        };
        window.addEventListener('keydown', handleKey);

        const cleanup = () => {
            modal.style.display = 'none';
            btnOk.onclick = null;
            window.removeEventListener('keydown', handleKey);
        };

        btnOk.onclick = () => { resolve(); cleanup(); };
    });
}

function customConfirm(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-confirm-modal');
        if (!modal) return resolve(confirm(message)); // fallback

        const msgEl = document.getElementById('custom-confirm-message');
        const btnOk = document.getElementById('custom-confirm-ok');
        const btnCancel = document.getElementById('custom-confirm-cancel');

        msgEl.textContent = message;
        modal.style.display = 'flex';

        const handleKey = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnOk.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                btnCancel.click();
            }
        };
        window.addEventListener('keydown', handleKey);

        const cleanup = () => {
            modal.style.display = 'none';
            btnOk.onclick = null;
            btnCancel.onclick = null;
            window.removeEventListener('keydown', handleKey);
        };

        btnOk.onclick = () => { resolve(true); cleanup(); };
        btnCancel.onclick = () => { resolve(false); cleanup(); };
    });
}

// Generic app interactions (modals, etc.)
document.addEventListener('DOMContentLoaded', () => {
    // Add subtle animations or handle flash messages here
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000); // auto-hide after 5 seconds
    });

    // File input visual feedback & list management
    const fileInputs = document.querySelectorAll('.file-input');
    
    fileInputs.forEach(input => {
        // We will store all selected files in a DataTransfer object
        let dataTransfer = new DataTransfer();
        const display = input.parentElement.querySelector('.file-text');
        // Find the sibling or parent-sibling .file-list container dynamically
        const fileListContainer = input.closest('.form-group').querySelector('.file-list');

        input.addEventListener('change', function(e) {
            // When new files are selected, append them to our DataTransfer object
            if (this.files) {
                for (let i = 0; i < this.files.length; i++) {
                    dataTransfer.items.add(this.files[i]);
                }
                
                // Update the actual input's files with our aggregated list
                this.files = dataTransfer.files;
                
                updateUI();
            }
        });

        function updateUI() {
            // Update the dropzone text
            if (display) {
                if (dataTransfer.files.length > 0) {
                    display.textContent = dataTransfer.files.length + ' files ready to upload';
                } else {
                    display.textContent = 'Choose GPX files or drag & drop them here';
                }
            }

            // Update the file list container if it exists
            if (fileListContainer) {
                fileListContainer.innerHTML = ''; // Clear current list
                
                Array.from(dataTransfer.files).forEach((file, index) => {
                    const li = document.createElement('li');
                    li.className = 'file-list-item glass';
                    
                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'file-name';
                    nameSpan.textContent = file.name;
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'remove-file';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.title = 'Remove this file';
                    
                    removeBtn.addEventListener('click', () => {
                        // Create a new DataTransfer, add all EXCEPT the removed one
                        const newDataTransfer = new DataTransfer();
                        Array.from(dataTransfer.files).forEach((f, i) => {
                            if (i !== index) {
                                newDataTransfer.items.add(f);
                            }
                        });
                        dataTransfer = newDataTransfer;
                        input.files = dataTransfer.files; // Update the real input
                        updateUI(); // Re-render
                    });
                    
                    li.appendChild(nameSpan);
                    li.appendChild(removeBtn);
                    fileListContainer.appendChild(li);
                });
            }
        }
    });

    // Handle deletion of existing tracks via AJAX
    const deleteExistingBtns = document.querySelectorAll('.delete-existing-track');
    deleteExistingBtns.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const confirmed = await customConfirm('Are you sure you want to delete this track? This action cannot be undone.');
            if (!confirmed) {
                return;
            }
            
            const stepId = this.getAttribute('data-id');
            const listItem = document.getElementById('step-' + stepId);
            
            fetch('?route=delete_track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ step_id: stepId })
            })
            .then(response => response.json())
            .then(async data => {
                if (data.success) {
                    // Remove from DOM
                    listItem.style.opacity = '0';
                    setTimeout(() => listItem.remove(), 300);
                } else {
                    await customAlert('Error: ' + (data.error || 'Failed to delete track.'));
                }
            })
            .catch(async err => {
                await customAlert('Network error while deleting track.');
            });
        });
    });
});
