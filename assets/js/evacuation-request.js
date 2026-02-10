/**
 * SafeHaven – js/evacuation-request.js
 * Priority pills, family counter, special-needs pills,
 * location update modal, and the form → success-screen state transition.
 */
document.addEventListener('DOMContentLoaded', () => {

    /* ── Priority pills (single-select) ──────── */
    const priorityPills = document.querySelectorAll('#priorityGrid .pri-pill');
    priorityPills.forEach(pill => {
        pill.addEventListener('click', () => {
            priorityPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
        });
    });

    /* ── Family member counter ────────────────── */
    let famCount = 1;
    const famCountEl = document.getElementById('famCount');
    const famMinus   = document.getElementById('famMinus');
    const famPlus    = document.getElementById('famPlus');

    if (famMinus) famMinus.addEventListener('click', () => {
        if (famCount > 1) { famCount--; famCountEl.textContent = famCount; }
    });
    if (famPlus) famPlus.addEventListener('click', () => {
        if (famCount < 20) { famCount++; famCountEl.textContent = famCount; }
    });

    /* ── Special needs pills (multi-select) ──── */
    const needsPills = document.querySelectorAll('#needsGrid .need-pill');
    needsPills.forEach(pill => {
        pill.addEventListener('click', () => pill.classList.toggle('active'));
    });

    /* ── Location Update Modal ──────────────── */
    const locationModal = document.getElementById('locationModal');
    const updateLocationBtn = document.getElementById('updateLocationBtn');
    const closeLocationModal = document.getElementById('closeLocationModal');
    const cancelLocationBtn = document.getElementById('cancelLocationBtn');
    const saveLocationBtn = document.getElementById('saveLocationBtn');
    const useGpsBtn = document.getElementById('useGpsBtn');
    const locationDisplay = document.getElementById('locationDisplay');

    const streetInput = document.getElementById('streetInput');
    const barangayInput = document.getElementById('barangayInput');
    const cityInput = document.getElementById('cityInput');

    // Open modal
    if (updateLocationBtn) {
        updateLocationBtn.addEventListener('click', (e) => {
            e.preventDefault();
            locationModal.classList.add('active');
        });
    }

    // Close modal function
    const closeModal = () => {
        locationModal.classList.remove('active');
    };

    // Close modal handlers
    if (closeLocationModal) closeLocationModal.addEventListener('click', closeModal);
    if (cancelLocationBtn) cancelLocationBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    locationModal.addEventListener('click', (e) => {
        if (e.target === locationModal) {
            closeModal();
        }
    });

    // Save location
    if (saveLocationBtn) {
        saveLocationBtn.addEventListener('click', () => {
            const street = streetInput.value.trim();
            const barangay = barangayInput.value;
            const city = cityInput.value.trim();

            if (!street || !barangay || !city) {
                alert('Please fill in all location fields');
                return;
            }

            // Update the location display
            locationDisplay.textContent = `${street}, ${barangay}, ${city}`;
            
            // Save to sessionStorage
            sessionStorage.setItem('evac_location', JSON.stringify({
                street, barangay, city
            }));

            closeModal();
        });
    }

    // Use GPS location
    if (useGpsBtn) {
        useGpsBtn.addEventListener('click', () => {
            if (navigator.geolocation) {
                useGpsBtn.disabled = true;
                useGpsBtn.textContent = 'Getting location...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;

                        // In a real app, you would reverse geocode these coordinates
                        // For now, we'll use a simulated address
                        streetInput.value = 'GPS Detected Location';
                        barangayInput.value = 'Barangay 18';
                        cityInput.value = 'Bacolod City';

                        alert(`Location detected!\nLatitude: ${lat.toFixed(6)}\nLongitude: ${lon.toFixed(6)}`);
                        
                        useGpsBtn.disabled = false;
                        useGpsBtn.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="10" r="3"/>
                                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/>
                            </svg>
                            Use My Current GPS Location
                        `;
                    },
                    (error) => {
                        alert('Unable to get your location. Please enter it manually.');
                        useGpsBtn.disabled = false;
                        useGpsBtn.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="10" r="3"/>
                                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/>
                            </svg>
                            Use My Current GPS Location
                        `;
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser');
            }
        });
    }

    // Load saved location from sessionStorage
    const savedLocation = sessionStorage.getItem('evac_location');
    if (savedLocation) {
        const loc = JSON.parse(savedLocation);
        locationDisplay.textContent = `${loc.street}, ${loc.barangay}, ${loc.city}`;
        streetInput.value = loc.street;
        barangayInput.value = loc.barangay;
        cityInput.value = loc.city;
    }

    /* ── Request Evacuation button ─────────────── */
    const btnRequest = document.getElementById('btnRequestEvac');

    if (btnRequest) {
        btnRequest.addEventListener('click', () => {
            // Collect form data
            const activeNeeds = [];
            needsPills.forEach(p => { if (p.classList.contains('active')) activeNeeds.push(p.textContent.trim()); });
            
            const activePriority = document.querySelector('#priorityGrid .pri-pill.active');
            const priority = activePriority ? activePriority.textContent.trim() : 'None';

            // Save to sessionStorage so the result page can display it
            sessionStorage.setItem('evac_family_count', famCount);
            sessionStorage.setItem('evac_special_needs', JSON.stringify(activeNeeds));
            sessionStorage.setItem('evac_priority', priority);

            // Show loading state
            btnRequest.disabled = true;
            btnRequest.textContent = 'Processing Request…';
            btnRequest.style.opacity = '0.6';

            // Simulate processing, then redirect to unified result page
            // The result page will randomly show approved or declined state
            setTimeout(() => {
                // 50/50 chance - in production this would be determined by actual capacity
                const isApproved = Math.random() > 0.5;
                window.location.href = `EvacuationResult.php?status=${isApproved ? 'approved' : 'declined'}`;
            }, 1200);
        });
    }

    /* ── Submit another request ───────────────── */
    const btnAnother = document.getElementById('btnAnother');
    if (btnAnother) {
        btnAnother.addEventListener('click', () => {
            successScreen.classList.remove('visible');
            formScreen.classList.remove('hidden');
            // Reset button
            if (btnRequest) { 
                btnRequest.disabled = false; 
                btnRequest.textContent = 'Request Evacuation'; 
                btnRequest.style.opacity = ''; 
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});