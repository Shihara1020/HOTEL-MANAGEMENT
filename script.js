document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            if (instance.input.id === 'check_in' || instance.input.id === 'check_out') {
                calculateTotalPrice();
            }
        }
    });

    // Calendar initialization
    const calendar = document.getElementById('calendar');
    const selectedDateInput = document.getElementById('selected_date');
    
    function renderCalendar() {
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        let html = `<div class="calendar-header">
                        <h3>${today.toLocaleString('default', { month: 'long', year: 'numeric' })}</h3>
                    </div>
                    <div class="calendar-grid">
                        <div class="calendar-weekdays">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>
                        <div class="calendar-days">`;
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay.getDay(); i++) {
            html += `<div class="calendar-day empty"></div>`;
        }
        
        // Add days of the month
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            const isToday = day === today.getDate() && month === today.getMonth();
            const isBooked = Math.random() > 0.8; // Simulate some booked days
            
            html += `<div class="calendar-day ${isToday ? 'today' : ''} ${isBooked ? 'booked' : ''}" 
                        data-date="${dateStr}">${day}</div>`;
        }
        
        html += `</div></div>`;
        calendar.innerHTML = html;
        
        // Add click event to days
        document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            day.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                selectedDateInput.value = date;
                
                // Highlight selected day
                document.querySelectorAll('.calendar-day').forEach(d => {
                    d.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });
    }
    
    renderCalendar();
    
    // Form validation
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        const checkIn = new Date(document.getElementById('check_in').value);
        const checkOut = new Date(document.getElementById('check_out').value);
        
        if (checkIn >= checkOut) {
            alert('Check-out date must be after check-in date');
            e.preventDefault();
        }
    });
    
    // Update room details when room is selected
    document.getElementById('room_number').addEventListener('change', updateRoomDetails);
    
    // Set room type when booking room is selected
    document.getElementById('booking_room_number').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('booking_room_type').value = selectedOption.getAttribute('data-type') || '';
    });
    
    // Responsive menu toggle for mobile
    const menuToggle = document.querySelector('.menu-toggle');
    menuToggle.addEventListener('click', function() {
        document.querySelector('nav ul').classList.toggle('active');
    });
});

// Room management functions
function editRoom(roomId) {
    fetch(`get_room.php?id=${roomId}`)
        .then(response => response.json())
        .then(room => {
            // Create edit form
            const form = document.createElement('form');
            form.id = 'editRoomForm';
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_room">
                <input type="hidden" name="id" value="${room.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label>Room Number:</label>
                        <input type="text" name="room_number" value="${room.room_number}" required>
                    </div>
                    <div class="form-group">
                        <label>Room Type:</label>
                        <select name="room_type" required>
                            <option value="Standard" ${room.room_type === 'Standard' ? 'selected' : ''}>Standard</option>
                            <option value="Deluxe" ${room.room_type === 'Deluxe' ? 'selected' : ''}>Deluxe</option>
                            <option value="Suite" ${room.room_type === 'Suite' ? 'selected' : ''}>Suite</option>
                            <option value="Executive" ${room.room_type === 'Executive' ? 'selected' : ''}>Executive</option>
                            <option value="Presidential" ${room.room_type === 'Presidential' ? 'selected' : ''}>Presidential</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price Per Night:</label>
                        <input type="number" name="price_per_night" value="${room.price_per_night}" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Max Occupancy:</label>
                        <input type="number" name="max_occupancy" value="${room.max_occupancy}" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="available" ${room.status === 'available' ? 'selected' : ''}>Available</option>
                        <option value="occupied" ${room.status === 'occupied' ? 'selected' : ''}>Occupied</option>
                        <option value="maintenance" ${room.status === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amenities:</label>
                    <textarea name="amenities" rows="3">${room.amenities || ''}</textarea>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3">${room.description || ''}</textarea>
                </div>
                <button type="submit" class="btn">Update Room</button>
            `;
            
            // Show in a modal dialog
            showModal('Edit Room', form);
        });
}

function updateRoomDetails() {
    const roomSelect = document.getElementById('room_number');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('room_type').value = selectedOption.getAttribute('data-type');
        document.getElementById('room_price').value = '$' + selectedOption.getAttribute('data-price');
    } else {
        document.getElementById('room_type').value = '';
        document.getElementById('room_price').value = '';
    }
    
    // Calculate total price when dates change
    calculateTotalPrice();
}

function calculateTotalPrice() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const roomSelect = document.getElementById('room_number');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const pricePerNight = parseFloat(selectedOption?.getAttribute('data-price')) || 0;
    
    if (checkIn && checkOut && pricePerNight > 0) {
        const nights = (new Date(checkOut) - new Date(checkIn)) / (1000 * 60 * 60 * 24);
        const totalPrice = nights * pricePerNight;
        document.getElementById('amount').value = totalPrice.toFixed(2);
    }
}

// Print receipt function
window.printReceipt = function(paymentId) {
    window.open(`receipt.php?payment_id=${paymentId}`, '_blank');
};

function showModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body"></div>
        </div>
    `;
    
    modal.querySelector('.modal-body').appendChild(content);
    document.body.appendChild(modal);
    
    // Close modal when clicking X or outside
    modal.querySelector('.close-modal').onclick = () => modal.remove();
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
    
    // Prevent form submission from closing the page
    if (content.tagName === 'FORM') {
        content.onsubmit = (e) => {
            e.preventDefault();
            // Handle form submission with AJAX
            fetch('process.php', {
                method: 'POST',
                body: new FormData(content)
            })
            .then(() => {
                modal.remove();
                window.location.reload();
            });
        };
    }
}