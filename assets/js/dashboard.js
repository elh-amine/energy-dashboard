// Configuration
const API_BASE = '../api/';
const REFRESH_INTERVAL = 60000; // 60 secondes

// Variables globales pour les graphiques
let energyFlowChart = null;
let housesDistributionChart = null;
let batteryHistoryChart = null;
let monthlyTrendChart = null;
let houseComparisonChart = null;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initFilters();
    loadDashboardData();
    initCharts();
    
    // Rafraîchissement automatique
    setInterval(loadDashboardData, REFRESH_INTERVAL);
    
    // Mise à jour de l'heure
    updateLastUpdate();
    setInterval(updateLastUpdate, 1000);
});

// Navigation entre sections
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item[data-section]');
    const sections = document.querySelectorAll('.content-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetSection = this.getAttribute('data-section');
            
            // Mettre à jour la navigation active
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Afficher la section correspondante
            sections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetSection) {
                    section.classList.add('active');
                }
            });
            
            // Charger les données spécifiques à la section
            loadSectionData(targetSection);
        });
    });
}

// Initialisation des filtres
function initFilters() {
    // Filtre de type de maison
    const typeFilter = document.getElementById('houseTypeFilter');
    if (typeFilter) {
        typeFilter.addEventListener('change', filterHouses);
    }
    
    // Recherche de maison
    const searchInput = document.getElementById('houseSearch');
    if (searchInput) {
        searchInput.addEventListener('input', filterHouses);
    }
    
    // Sélection de période pour statistiques
    const periodButtons = document.querySelectorAll('.period-btn');
    periodButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            periodButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const period = this.getAttribute('data-period');
            loadStatistics(period);
        });
    });
    
    // Génération de facturation
    const generateBillingBtn = document.getElementById('generateBilling');
    if (generateBillingBtn) {
        generateBillingBtn.addEventListener('click', generateBilling);
    }
    
    // Populate billing months
    populateBillingMonths();
}

// Charger les données du dashboard
async function loadDashboardData() {
    try {
        const response = await fetch(API_BASE + 'get_energy_data.php?action=overview');
        const data = await response.json();
        
        if (data.success) {
            updateKPIs(data.data);
            updateActivityFeed(data.data.recent_activity || []);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    }
}

// Charger les données d'une section spécifique
async function loadSectionData(section) {
    switch(section) {
        case 'houses':
            await loadHouses();
            break;
        case 'battery':
            await loadBatteryData();
            break;
        case 'statistics':
            await loadStatistics('today');
            break;
        case 'billing':
            // Les données de facturation sont chargées à la demande
            break;
    }
}

// Mettre à jour les KPIs
function updateKPIs(data) {
    document.getElementById('totalInjected').textContent = (data.total_injected || 0).toFixed(2) + ' kWh';
    document.getElementById('totalTaken').textContent = (data.total_taken || 0).toFixed(2) + ' kWh';
    
    const balance = (data.total_injected || 0) - (data.total_taken || 0);
    document.getElementById('energyBalance').textContent = balance.toFixed(2) + ' kWh';
    
    document.getElementById('activeHouses').textContent = data.active_houses || 0;
}

// Mettre à jour le fil d'activité
function updateActivityFeed(activities) {
    const feed = document.getElementById('activityFeed');
    
    if (activities.length === 0) {
        feed.innerHTML = '<p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucune activité récente</p>';
        return;
    }
    
    feed.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="time">${formatDateTime(activity.timestamp)}</div>
            <div class="message">${activity.message}</div>
        </div>
    `).join('');
}

// Charger les maisons
async function loadHouses() {
    try {
        const response = await fetch(API_BASE + 'get_energy_data.php?action=houses');
        const data = await response.json();
        
        if (data.success) {
            displayHouses(data.data);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des maisons:', error);
    }
}

// Afficher les maisons
function displayHouses(houses) {
    const grid = document.getElementById('housesGrid');
    
    if (houses.length === 0) {
        grid.innerHTML = '<p style="text-align: center; color: #7f8c8d; padding: 40px;">Aucune maison trouvée</p>';
        return;
    }
    
    grid.innerHTML = houses.map(house => `
        <div class="house-card" onclick="viewHouseDetails(${house.id})">
            <div class="house-header">
                <h3 class="house-name">${house.name}</h3>
                <span class="house-type ${house.type}">${house.type}</span>
            </div>
            <div class="house-stats">
                <div class="stat-row">
                    <span class="label">Énergie injectée</span>
                    <span class="value">${(house.energy_injected || 0).toFixed(2)} kWh</span>
                </div>
                <div class="stat-row">
                    <span class="label">Énergie soutirée</span>
                    <span class="value">${(house.energy_taken || 0).toFixed(2)} kWh</span>
                </div>
                <div class="stat-row">
                    <span class="label">Balance</span>
                    <span class="value">${((house.energy_injected || 0) - (house.energy_taken || 0)).toFixed(2)} kWh</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Filtrer les maisons
function filterHouses() {
    const typeFilter = document.getElementById('houseTypeFilter').value;
    const searchQuery = document.getElementById('houseSearch').value.toLowerCase();
    
    const cards = document.querySelectorAll('.house-card');
    
    cards.forEach(card => {
        const houseName = card.querySelector('.house-name').textContent.toLowerCase();
        const houseType = card.querySelector('.house-type').textContent;
        
        const matchesType = typeFilter === 'all' || houseType === typeFilter;
        const matchesSearch = houseName.includes(searchQuery);
        
        if (matchesType && matchesSearch) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Voir les détails d'une maison
function viewHouseDetails(houseId) {
    window.location.href = `house_details.php?id=${houseId}`;
}

// Charger les données de la batterie
async function loadBatteryData() {
    try {
        const response = await fetch(API_BASE + 'get_energy_data.php?action=battery');
        const data = await response.json();
        
        if (data.success) {
            updateBatteryDisplay(data.data);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des données de la batterie:', error);
    }
}

// Mettre à jour l'affichage de la batterie
function updateBatteryDisplay(data) {
    // Simuler un niveau de batterie (à adapter selon vos données réelles)
    const batteryLevel = 75; // Pourcentage
    
    document.getElementById('batteryFillLevel').style.height = batteryLevel + '%';
    document.getElementById('batteryPercentage').textContent = batteryLevel + '%';
    
    document.getElementById('batteryReceived').textContent = (data.total_received || 0).toFixed(2) + ' kWh';
    document.getElementById('batteryDistributed').textContent = (data.total_distributed || 0).toFixed(2) + ' kWh';
    
    const efficiency = data.total_received > 0 ? ((data.total_distributed / data.total_received) * 100) : 0;
    document.getElementById('systemEfficiency').textContent = efficiency.toFixed(1) + '%';
}

// Charger les statistiques
async function loadStatistics(period) {
    try {
        const response = await fetch(API_BASE + `get_energy_data.php?action=statistics&period=${period}`);
        const data = await response.json();
        
        if (data.success) {
            updateStatisticsCharts(data.data);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
    }
}

// Initialiser les graphiques
function initCharts() {
    // Graphique des échanges énergétiques
    const energyFlowCtx = document.getElementById('energyFlowChart');
    if (energyFlowCtx) {
        energyFlowChart = new Chart(energyFlowCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Énergie Injectée',
                        data: [],
                        borderColor: '#38ef7d',
                        backgroundColor: 'rgba(56, 239, 125, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Énergie Soutirée',
                        data: [],
                        borderColor: '#ff6a00',
                        backgroundColor: 'rgba(255, 106, 0, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Énergie (kWh)'
                        }
                    }
                }
            }
        });
    }
    
    // Graphique de répartition
    const distributionCtx = document.getElementById('housesDistributionChart');
    if (distributionCtx) {
        housesDistributionChart = new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Productrices', 'Consommatrices'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#38ef7d', '#ff6a00']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Graphique d'historique de batterie
    const batteryHistoryCtx = document.getElementById('batteryHistoryChart');
    if (batteryHistoryCtx) {
        batteryHistoryChart = new Chart(batteryHistoryCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Reçue',
                        data: [],
                        backgroundColor: '#38ef7d'
                    },
                    {
                        label: 'Distribuée',
                        data: [],
                        backgroundColor: '#ff6a00'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Énergie (kWh)'
                        }
                    }
                }
            }
        });
    }
    
    // Graphiques de statistiques
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
    if (monthlyTrendCtx) {
        monthlyTrendChart = new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tendance énergétique',
                    data: [],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }
    
    const houseComparisonCtx = document.getElementById('houseComparisonChart');
    if (houseComparisonCtx) {
        houseComparisonChart = new Chart(houseComparisonCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Énergie totale (kWh)',
                    data: [],
                    backgroundColor: '#764ba2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y'
            }
        });
    }
}

// Mettre à jour les graphiques de statistiques
function updateStatisticsCharts(data) {
    // Mise à jour du graphique de tendance
    if (monthlyTrendChart && data.trend) {
        monthlyTrendChart.data.labels = data.trend.labels || [];
        monthlyTrendChart.data.datasets[0].data = data.trend.values || [];
        monthlyTrendChart.update();
    }
    
    // Mise à jour du graphique de comparaison
    if (houseComparisonChart && data.comparison) {
        houseComparisonChart.data.labels = data.comparison.labels || [];
        houseComparisonChart.data.datasets[0].data = data.comparison.values || [];
        houseComparisonChart.update();
    }
}

// Générer la facturation
async function generateBilling() {
    const month = document.getElementById('billingMonth').value;
    
    if (!month) {
        alert('Veuillez sélectionner un mois');
        return;
    }
    
    try {
        const response = await fetch(API_BASE + `billing.php?month=${month}`);
        const data = await response.json();
        
        if (data.success) {
            displayBillingTable(data.data);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la génération de la facturation:', error);
        alert('Erreur lors de la génération de la facturation');
    }
}

// Afficher le tableau de facturation
function displayBillingTable(billingData) {
    const container = document.getElementById('billingTable');
    
    if (billingData.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #7f8c8d; padding: 40px;">Aucune donnée de facturation pour ce mois</p>';
        return;
    }
    
    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Maison</th>
                    <th>Type</th>
                    <th>Énergie Injectée (kWh)</th>
                    <th>Énergie Soutirée (kWh)</th>
                    <th>Montant (€)</th>
                </tr>
            </thead>
            <tbody>
                ${billingData.map(row => `
                    <tr>
                        <td>${row.house_name}</td>
                        <td><span class="house-type ${row.type}">${row.type}</span></td>
                        <td>${parseFloat(row.total_energy_injected).toFixed(2)}</td>
                        <td>${parseFloat(row.total_energy_taken).toFixed(2)}</td>
                        <td style="font-weight: bold; color: ${row.amount_to_pay >= 0 ? '#c0392b' : '#27ae60'}">
                            ${parseFloat(row.amount_to_pay).toFixed(2)} €
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Peupler le sélecteur de mois pour la facturation
function populateBillingMonths() {
    const select = document.getElementById('billingMonth');
    if (!select) return;
    
    const currentDate = new Date();
    
    // Générer les 12 derniers mois
    for (let i = 0; i < 12; i++) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
        const month = date.toISOString().substring(0, 7);
        const monthName = date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' });
        
        const option = document.createElement('option');
        option.value = month;
        option.textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);
        select.appendChild(option);
    }
}

// Mettre à jour l'heure de dernière mise à jour
function updateLastUpdate() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR');
    document.getElementById('lastUpdate').textContent = timeString;
}

// Formater une date/heure
function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('fr-FR');
}