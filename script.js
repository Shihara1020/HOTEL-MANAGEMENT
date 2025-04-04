document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // Calendar initialization
    const calendar = document.getElementById('calendar');
    const selectedDateInput = document.getElementById('selected_date');
    
    // Simple calendar implementation (for demo)
    // In a real app, you might use a library like FullCalendar
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
    
    // Print receipt function
    window.printReceipt = function(paymentId) {
        // In a real app, this would generate a PDF or open a print dialog
        // For demo, we'll just show an alert
        alert(`Printing receipt for payment ID: ${paymentId}`);
        
        // In a complete implementation, you might:
        // 1. Send an AJAX request to generate a PDF receipt
        // 2. Open the PDF in a new window for printing
        // 3. Or generate an HTML receipt and use window.print()
    };
    
    // Responsive menu toggle for mobile
    const menuToggle = document.createElement('div');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.querySelector('header').prepend(menuToggle);
    
    menuToggle.addEventListener('click', function() {
        document.querySelector('nav ul').classList.toggle('active');
    });
});