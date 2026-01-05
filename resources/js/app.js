import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


import { renderUserDailyChart } from './charts/userDailyChart';

// Biar grafik ke-load pas DOM siap
document.addEventListener('DOMContentLoaded', () => {
    const dailyLabels = window.dailyUsersLabels || [];
    const dailyData = window.dailyUsersData || [];

    if (document.getElementById('userChartDaily')) {
        renderUserDailyChart(dailyLabels, dailyData);
    }
});