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
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this track? This action cannot be undone.')) {
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
            .then(data => {
                if (data.success) {
                    // Remove from DOM
                    listItem.style.opacity = '0';
                    setTimeout(() => listItem.remove(), 300);
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete track.'));
                }
            })
            .catch(err => {
                alert('Network error while deleting track.');
            });
        });
    });
});
