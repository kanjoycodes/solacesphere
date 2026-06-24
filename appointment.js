// Appointment Booking System JavaScript

// Helper functions (defined first so they can be used by other functions)
function formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getMonday(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

function formatDate(date) {
    const options = { weekday: 'short', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

// Generate a random schedule for demonstration
function generateSchedule() {
    const schedule = {};
    const today = new Date();
    
    for (let i = 0; i < 60; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() + i);
        const dateStr = formatDateKey(date);
        
        schedule[dateStr] = {
            available: ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            booked: generateRandomBooked(['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']),
            pending: []
        };
    }
    
    return schedule;
}

function generateRandomBooked(available) {
    return available.filter(() => Math.random() < 0.3);
}

// Data structure for clinicians and their schedules
const cliniciansData = {
    1: {
        id: 1,
        name: 'Dr. Ruto',
        specialization: 'Clinical Psychologist',
        photo: 'images/signup.jfif',
        schedule: generateSchedule()
    },
    2: {
        id: 2,
        name: 'Dr. Jane',
        specialization: 'Therapist',
        photo: 'images/signup.jfif',
        schedule: generateSchedule()
    },
    3: {
        id: 3,
        name: 'Dr. Ahmed',
        specialization: 'Psychiatrist',
        photo: 'images/signup.jfif',
        schedule: generateSchedule()
    },
    4: {
        id: 4,
        name: 'Sarah Wilson',
        specialization: 'Counselor',
        photo: 'images/signup.jfif',
        schedule: generateSchedule()
    }
};

// Global state
let selectedClinicianId = null;
let selectedDate = null;
let selectedTime = null;
let currentWeekStart = getMonday(new Date());

// Initialize the page (robust to when the script is loaded)
function initAppointmentSystem() {
    console.log('Initializing appointment system...');
    try {
        setupEventListeners();
        initializeCalendar();
        console.log('Appointment system initialized');
    } catch (err) {
        console.error('Error initializing appointment system:', err);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppointmentSystem);
} else {
    // DOM already ready
    initAppointmentSystem();
}

// Setup all event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    // Clinician selection
    const buttons = document.querySelectorAll('.select-clinician-btn');
    console.log('Found ' + buttons.length + ' clinician buttons');
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('Button clicked!');
            e.preventDefault();
            const clinicianCard = this.closest('.clinician-card');
            const clinicianId = clinicianCard.dataset.clinicianId;
            console.log('Selected clinician ID: ' + clinicianId);
            selectClinicianAndLoadSchedule(clinicianId);
        });
    });

    // Calendar navigation
    document.getElementById('prev-week').addEventListener('click', goToPreviousWeek);
    document.getElementById('next-week').addEventListener('click', goToNextWeek);
    document.getElementById('view-toggle').addEventListener('change', changeCalendarView);

    // Modal close buttons
    document.getElementById('close-modal').addEventListener('click', closeBookingModal);
    document.getElementById('cancel-booking').addEventListener('click', closeBookingModal);
    document.getElementById('close-success').addEventListener('click', closeSuccessModal);

    // Form submission
    document.getElementById('booking-form').addEventListener('submit', submitBooking);

    // Clinician search
    document.querySelector('.clinician-search').addEventListener('input', searchClinicians);

    // Click outside modal to close
    document.getElementById('booking-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeBookingModal();
        }
    });

    document.getElementById('success-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSuccessModal();
        }
    });
}

// Select a clinician and load their schedule
function selectClinicianAndLoadSchedule(clinicianId) {
    console.log('selectClinicianAndLoadSchedule called with ID: ' + clinicianId);
    selectedClinicianId = clinicianId;
    
    // Update UI - highlight selected card
    document.querySelectorAll('.clinician-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-clinician-id="${clinicianId}"]`).classList.add('selected');

    // Update header
    const clinician = cliniciansData[clinicianId];
    console.log('Clinician data: ', clinician);
    document.getElementById('selected-clinician-name').textContent = clinician.name + ' - ' + clinician.specialization;

    // Load and display calendar
    console.log('Loading calendar...');
    loadAndDisplayCalendar();
    console.log('Calendar loaded');
}

// Initialize calendar on page load
function initializeCalendar() {
    updateCurrentDate();
}

// Load and display the calendar grid
function loadAndDisplayCalendar() {
    console.log('loadAndDisplayCalendar called, selectedClinicianId: ' + selectedClinicianId);
    if (!selectedClinicianId) {
        console.error('No clinician selected!');
        return;
    }

    const clinician = cliniciansData[selectedClinicianId];
    console.log('Got clinician data: ', clinician);
    const calendarGrid = document.getElementById('calendar-grid');
    console.log('Calendar grid element: ', calendarGrid);
    
    if (!calendarGrid) {
        console.error('Calendar grid element not found!');
        return;
    }
    
    calendarGrid.innerHTML = '';
    console.log('Calendar grid cleared');

    // Get 7 days starting from currentWeekStart
    for (let i = 0; i < 7; i++) {
        const date = new Date(currentWeekStart);
        date.setDate(date.getDate() + i);
        const dateStr = formatDateKey(date);
        const dayData = clinician.schedule[dateStr];
        console.log('Creating day column for ' + dateStr + ', dayData: ', dayData);

        const dayColumn = createDayColumn(date, dayData);
        calendarGrid.appendChild(dayColumn);
    }
    console.log('Calendar grid populated with all 7 day columns');
}

function createDayColumn(date, dayData) {
    console.log('createDayColumn called with date: ' + date.toLocaleDateString() + ', dayData exists: ' + (dayData ? 'yes' : 'no'));
    const column = document.createElement('div');
    column.className = 'day-column';

    // Day header
    const header = document.createElement('div');
    header.className = 'day-header';
    
    // Create date parts separately
    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
    const monthDay = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    
    header.innerHTML = `
        <div>${dayName}</div>
        <div class="day-header-date">${monthDay}</div>
    `;
    column.appendChild(header);
    console.log('Day header created: ' + dayName + ' ' + monthDay);

    // Time slots
    if (dayData) {
        console.log('Adding time slots, available count: ' + dayData.available.length);
        dayData.available.forEach(time => {
            const slot = document.createElement('button');
            slot.className = 'time-slot';
            slot.textContent = formatTime(time);

            if (dayData.booked.includes(time)) {
                slot.classList.add('booked');
                slot.disabled = true;
                console.log('Slot ' + time + ' is booked');
            } else if (dayData.pending.includes(time)) {
                slot.classList.add('pending');
                slot.disabled = true;
                console.log('Slot ' + time + ' is pending');
            } else {
                slot.classList.add('available');
                console.log('Slot ' + time + ' is available');
                slot.addEventListener('click', function() {
                    console.log('Time slot clicked: ' + time);
                    openBookingModal(date, time);
                });
            }

            column.appendChild(slot);
        });
    } else {
        console.warn('No dayData provided for ' + date.toLocaleDateString());
    }

    return column;
}

// Navigate to previous week
function goToPreviousWeek() {
    currentWeekStart = new Date(currentWeekStart);
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    updateCurrentDate();
    loadAndDisplayCalendar();
}

// Navigate to next week
function goToNextWeek() {
    currentWeekStart = new Date(currentWeekStart);
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    updateCurrentDate();
    loadAndDisplayCalendar();
}

// Update the current date display
function updateCurrentDate() {
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    const startStr = formatDate(currentWeekStart);
    const endStr = formatDate(weekEnd);
    
    document.getElementById('current-date').textContent = `${startStr} - ${endStr}`;
}

// Change calendar view (weekly vs monthly)
function changeCalendarView() {
    const view = document.getElementById('view-toggle').value;
    if (view === 'monthly') {
        alert('Monthly view coming soon!');
        document.getElementById('view-toggle').value = 'weekly';
    }
}

// Search clinicians by name or specialization
function searchClinicians() {
    const searchTerm = document.querySelector('.clinician-search').value.toLowerCase();
    
    document.querySelectorAll('.clinician-card').forEach(card => {
        const name = card.querySelector('.clinician-name').textContent.toLowerCase();
        const spec = card.querySelector('.clinician-spec').textContent.toLowerCase();
        const specializations = card.querySelector('.specializations').textContent.toLowerCase();

        if (name.includes(searchTerm) || spec.includes(searchTerm) || specializations.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Open booking modal
function openBookingModal(date, time) {
    console.log('openBookingModal called with date: ' + date.toLocaleDateString() + ', time: ' + time);
    selectedDate = date;
    selectedTime = time;

    const clinician = cliniciansData[selectedClinicianId];
    console.log('Current clinician: ', clinician);
    
    // Populate form
    console.log('Populating form fields...');
    document.getElementById('clinician-name').value = clinician.name;
    document.getElementById('selected-date').value = formatDate(date);
    document.getElementById('selected-time').value = formatTime(time);
    console.log('Form fields populated');

    // Show modal
    const modal = document.getElementById('booking-modal');
    console.log('Booking modal element: ', modal);
    modal.classList.add('active');
    console.log('Modal made visible');
}

// Close booking modal
function closeBookingModal() {
    document.getElementById('booking-modal').classList.remove('active');
    document.getElementById('booking-form').reset();
}

// Submit booking form
function submitBooking(e) {
    console.log('submitBooking called');
    e.preventDefault();

    const clinician = cliniciansData[selectedClinicianId];
    const sessionType = document.getElementById('session-type').value;
    const reasonNote = document.getElementById('reason-note').value;
    
    console.log('Session type: ' + sessionType);
    console.log('Reason note: ' + reasonNote);

    // Validate session type is selected
    if (!sessionType) {
        console.error('Session type not selected');
        alert('Please select a session type');
        return;
    }

    // Store the pending booking
    const dateStr = formatDateKey(selectedDate);
    console.log('Storing pending booking for date: ' + dateStr + ', time: ' + selectedTime);
    if (!clinician.schedule[dateStr].pending.includes(selectedTime)) {
        clinician.schedule[dateStr].pending.push(selectedTime);
        console.log('Added to pending');
    }

    // Close booking modal and show success
    closeBookingModal();
    showSuccessModal(clinician, sessionType, reasonNote);

    // Refresh calendar
    loadAndDisplayCalendar();
    console.log('Calendar refreshed after booking');
}

// Show success modal
function showSuccessModal(clinician, sessionType, reasonNote) {
    console.log('showSuccessModal called with clinician: ' + clinician.name + ', sessionType: ' + sessionType);
    const successModal = document.getElementById('success-modal');
    console.log('Success modal element: ', successModal);
    
    document.getElementById('success-message').textContent = `
        Your appointment request with ${clinician.name} has been submitted successfully!
    `;
    console.log('Success message set');

    const sessionTypeLabel = {
        'video': 'Video Call',
        'in-person': 'In-Person',
        'text': 'Text Consultation'
    };

    const detailsHtml = `
        <p><strong>Professional:</strong> <span>${clinician.name}</span></p>
        <p><strong>Specialization:</strong> <span>${clinician.specialization}</span></p>
        <p><strong>Date:</strong> <span>${formatDate(selectedDate)}</span></p>
        <p><strong>Time:</strong> <span>${formatTime(selectedTime)}</span></p>
        <p><strong>Session Type:</strong> <span>${sessionTypeLabel[sessionType] || sessionType}</span></p>
        ${reasonNote ? `<p><strong>Reason:</strong> <span>${reasonNote}</span></p>` : ''}
    `;
    document.getElementById('success-details').innerHTML = detailsHtml;
    console.log('Success details populated');

    successModal.classList.add('active');
    console.log('Success modal displayed');
}

// Close success modal
function closeSuccessModal() {
    document.getElementById('success-modal').classList.remove('active');
}
