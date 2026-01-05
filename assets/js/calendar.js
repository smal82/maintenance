// assets/js/calendar.js

const Calendar = {
    currentDate: new Date(),
    selectedDate: null,
    events: [],
    
    init: function(containerId) {
        this.container = $(`#${containerId}`);
        this.render();
        this.attachEvents();
    },
    
    render: function() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        const html = `
            <div class="calendar">
                <div class="calendar-header">
                    <button class="calendar-nav" id="prevMonth">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h3 class="calendar-title">
                        ${this.getMonthName(month)} ${year}
                    </h3>
                    <button class="calendar-nav" id="nextMonth">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-weekdays">
                    ${this.renderWeekdays()}
                </div>
                <div class="calendar-days">
                    ${this.renderDays(year, month)}
                </div>
            </div>
        `;
        
        this.container.html(html);
        this.addStyles();
    },
    
    renderWeekdays: function() {
        const weekdays = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
        return weekdays.map(day => `<div class="calendar-weekday">${day}</div>`).join('');
    },
    
    renderDays: function(year, month) {
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        let html = '';
        const startDay = firstDay === 0 ? 6 : firstDay - 1;
        
        // Previous month days
        for (let i = startDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            html += `<div class="calendar-day other-month">${day}</div>`;
        }
        
        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = this.formatDate(date);
            const isToday = this.isToday(date);
            const isSelected = this.isSelected(date);
            const hasEvents = this.hasEvents(dateStr);
            
            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            if (hasEvents) classes += ' has-events';
            
            html += `
                <div class="${classes}" data-date="${dateStr}">
                    <span class="day-number">${day}</span>
                    ${hasEvents ? '<div class="event-dots">' + this.renderEventDots(dateStr) + '</div>' : ''}
                </div>
            `;
        }
        
        // Next month days
        const totalCells = startDay + daysInMonth;
        const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let day = 1; day <= remainingCells; day++) {
            html += `<div class="calendar-day other-month">${day}</div>`;
        }
        
        return html;
    },
    
    renderEventDots: function(dateStr) {
        const dayEvents = this.events.filter(e => e.date === dateStr);
        return dayEvents.slice(0, 3).map(e => 
            `<span class="event-dot" style="background-color: ${e.color || '#3498db'}"></span>`
        ).join('');
    },
    
    attachEvents: function() {
        const self = this;
        
        this.container.on('click', '#prevMonth', function() {
            self.currentDate.setMonth(self.currentDate.getMonth() - 1);
            self.render();
            self.attachEvents();
            self.onMonthChange();
        });
        
        this.container.on('click', '#nextMonth', function() {
            self.currentDate.setMonth(self.currentDate.getMonth() + 1);
            self.render();
            self.attachEvents();
            self.onMonthChange();
        });
        
        this.container.on('click', '.calendar-day:not(.other-month)', function() {
            const date = $(this).data('date');
            self.selectDate(date);
            self.onDateClick(date);
        });
    },
    
    selectDate: function(dateStr) {
        this.selectedDate = dateStr;
        this.container.find('.calendar-day').removeClass('selected');
        this.container.find(`.calendar-day[data-date="${dateStr}"]`).addClass('selected');
    },
    
    loadEvents: function(year, month) {
        Ajax.get(
            APP_CONFIG.baseUrl + '/api/calendar.php',
            { year, month },
            (response) => {
                if (response.success) {
                    this.events = response.events;
                    this.render();
                    this.attachEvents();
                }
            }
        );
    },
    
    hasEvents: function(dateStr) {
        return this.events.some(e => e.date === dateStr);
    },
    
    isToday: function(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    },
    
    isSelected: function(date) {
        if (!this.selectedDate) return false;
        return this.formatDate(date) === this.selectedDate;
    },
    
    formatDate: function(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },
    
    getMonthName: function(month) {
        const months = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                       'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        return months[month];
    },
    
    onMonthChange: function() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth() + 1;
        this.loadEvents(year, month);
    },
    
    onDateClick: function(date) {
        // Override this method to handle date clicks
        console.log('Date clicked:', date);
    },
    
    addStyles: function() {
        if ($('#calendar-styles').length) return;
        
        $('head').append(`
            <style id="calendar-styles">
                .calendar {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .calendar-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 20px;
                }
                .calendar-title {
                    font-size: 1.25rem;
                    font-weight: 600;
                }
                .calendar-nav {
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.2s;
                }
                .calendar-nav:hover {
                    background-color: var(--color-bg);
                }
                .calendar-weekdays {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    gap: 8px;
                    margin-bottom: 8px;
                }
                .calendar-weekday {
                    text-align: center;
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: var(--color-text-light);
                    padding: 8px;
                }
                .calendar-days {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    gap: 8px;
                }
                .calendar-day {
                    aspect-ratio: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 8px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    position: relative;
                }
                .calendar-day:not(.other-month):hover {
                    background-color: var(--color-primary-lighter);
                }
                .calendar-day.other-month {
                    color: var(--color-text-lighter);
                    cursor: default;
                }
                .calendar-day.today {
                    background-color: var(--color-primary);
                    color: white;
                    font-weight: 600;
                }
                .calendar-day.selected {
                    background-color: var(--color-primary-dark);
                    color: white;
                }
                .calendar-day.has-events .day-number {
                    font-weight: 600;
                }
                .day-number {
                    font-size: 0.875rem;
                }
                .event-dots {
                    display: flex;
                    gap: 2px;
                    margin-top: 4px;
                }
                .event-dot {
                    width: 4px;
                    height: 4px;
                    border-radius: 50%;
                }
            </style>
        `);
    }
};

// Initialize calendar on page load if element exists
$(document).ready(function() {
    if ($('#maintenanceCalendar').length) {
        Calendar.init('maintenanceCalendar');
        Calendar.onDateClick = function(date) {
            // Load events for selected date
            console.log('Selected date:', date);
        };
    }
});