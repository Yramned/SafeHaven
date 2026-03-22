/**
 * SafeHaven - Evacuation Request JavaScript
 * Handles the evacuation request form and submission
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Get elements
    const evacuationFormScreen = document.getElementById('evacuationFormScreen');
    const evacuationSuccessScreen = document.getElementById('evacuationSuccessScreen');
    const btnRequestEvac = document.getElementById('btnRequestEvac');
    const btnAnother = document.getElementById('btnAnother');
    
    // Location elements
    const locationDisplay = document.getElementById('locationDisplay');
    const updateLocationBtn = document.getElementById('updateLocationBtn');
    const locationModal = document.getElementById('locationModal');
    const closeLocationModal = document.getElementById('closeLocationModal');
    const cancelLocationBtn = document.getElementById('cancelLocationBtn');
    const saveLocationBtn = document.getElementById('saveLocationBtn');
    const useGpsBtn = document.getElementById('useGpsBtn');
    
    const streetInput = document.getElementById('streetInput');
    const barangayInput = document.getElementById('barangayInput');
    const cityInput = document.getElementById('cityInput');
    
    // Priority and needs
    const priorityGrid = document.getElementById('priorityGrid');
    const needsGrid = document.getElementById('needsGrid');
    
    // Family counter
    const famMinus = document.getElementById('famMinus');
    const famPlus = document.getElementById('famPlus');
    const famCount = document.getElementById('famCount');
    
    let selectedPriority = 'unaccompanied';
    let selectedNeeds = ['wheelchair'];
    let familyMembers = 1;
    let locationData = {
        street: 'Street',
        barangay: 'Barangay area',
        city: 'Bacolod City',
        latitude: null,
        longitude: null
    };
    
    // Priority pill selection
    if (priorityGrid) {
        priorityGrid.querySelectorAll('.pri-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                // Remove active from all
                priorityGrid.querySelectorAll('.pri-pill').forEach(p => {
                    p.classList.remove('active');
                    p.setAttribute('aria-pressed', 'false');
                });
                
                // Add active to this one
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                selectedPriority = this.getAttribute('data-val');
            });
        });
    }
    
    // Special needs pill selection (multiple)
    if (needsGrid) {
        needsGrid.querySelectorAll('.need-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                const value = this.getAttribute('data-val');
                
                if (this.classList.contains('active')) {
                    this.classList.remove('active');
                    this.setAttribute('aria-pressed', 'false');
                    selectedNeeds = selectedNeeds.filter(n => n !== value);
                } else {
                    this.classList.add('active');
                    this.setAttribute('aria-pressed', 'true');
                    selectedNeeds.push(value);
                }
            });
        });
    }
    
    // Family counter
    if (famMinus) {
        famMinus.addEventListener('click', function() {
            if (familyMembers > 1) {
                familyMembers--;
                famCount.textContent = familyMembers;
            }
        });
    }
    
    if (famPlus) {
        famPlus.addEventListener('click', function() {
            if (familyMembers < 20) {
                familyMembers++;
                famCount.textContent = familyMembers;
            }
        });
    }
    
    // Location modal
    if (updateLocationBtn) {
        updateLocationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            locationModal.style.display = 'flex';
        });
    }
    
    if (closeLocationModal) {
        closeLocationModal.addEventListener('click', function() {
            locationModal.style.display = 'none';
        });
    }
    
    if (cancelLocationBtn) {
        cancelLocationBtn.addEventListener('click', function() {
            locationModal.style.display = 'none';
        });
    }
    
    if (saveLocationBtn) {
        saveLocationBtn.addEventListener('click', function() {
            const street = streetInput.value.trim();
            const barangay = barangayInput.value.trim();
            const city = cityInput.value.trim();
            
            if (street && barangay) {
                locationData.street = street;
                locationData.barangay = barangay;
                locationData.city = city;
                locationDisplay.textContent = `${street}, ${barangay}`;
                locationModal.style.display = 'none';
            }
        });
    }
    
    if (useGpsBtn) {
        useGpsBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                this.textContent = 'Getting location...';
                this.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        locationData.latitude = position.coords.latitude;
                        locationData.longitude = position.coords.longitude;
                        
                        // You could use reverse geocoding here
                        this.textContent = 'OK GPS Location Captured';
                        setTimeout(() => {
                            this.textContent = 'Use My Current GPS Location';
                            this.disabled = false;
                        }, 2000);
                    },
                    (error) => {
                        alert('Could not get your location. Please enter manually.');
                        this.textContent = 'Use My Current GPS Location';
                        this.disabled = false;
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    }
    
    // Submit evacuation request
    if (btnRequestEvac) {
        btnRequestEvac.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Disable button to prevent double submission
            this.disabled = true;
            this.textContent = 'Submitting...';
            
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            // Prepare request data
            const requestData = {
                csrf_token: csrfToken,
                location_street: locationData.street,
                location_barangay: locationData.barangay,
                location_city: locationData.city,
                location_latitude: locationData.latitude,
                location_longitude: locationData.longitude,
                priority: selectedPriority,
                family_members: familyMembers,
                special_needs: selectedNeeds
            };
            
            // Submit via AJAX
            fetch('index.php?page=evacuation-request-submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide form, show success screen
                    evacuationFormScreen.style.display = 'none';
                    evacuationSuccessScreen.style.display = 'block';
                    
                    // Update success screen with response data
                    updateSuccessScreen(data);
                } else {
                    alert(data.message || 'Failed to submit request. Please try again.');
                    btnRequestEvac.disabled = false;
                    btnRequestEvac.textContent = 'Request Evacuation';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btnRequestEvac.disabled = false;
                btnRequestEvac.textContent = 'Request Evacuation';
            });
        });
    }
    
    // Update success screen with response data
    function updateSuccessScreen(data) {
        // Update confirmation code
        const confCodeEl = document.getElementById('successConfCode');
        if (confCodeEl && data.request.confirmation_code) {
            confCodeEl.textContent = data.request.confirmation_code;
        }
        
        // Update center name
        const centerNameEl = document.querySelector('.center-name');
        if (centerNameEl && data.center.name) {
            centerNameEl.textContent = data.center.name;
        }
        
        // Update center address
        const centerAddressEl = document.querySelector('.center-address');
        if (centerAddressEl && data.center.address) {
            centerAddressEl.textContent = data.center.address;
        }
        
        // Update distance
        const distanceEl = document.querySelector('.cstat-val');
        if (distanceEl && data.center.distance) {
            distanceEl.textContent = data.center.distance;
        }
        
        // Update travel time
        const travelTimeEls = document.querySelectorAll('.cstat-val');
        if (travelTimeEls[1] && data.center.travel_time) {
            travelTimeEls[1].textContent = data.center.travel_time;
        }
        
        // Update family members
        const famCountEl = document.getElementById('successFamCount');
        if (famCountEl && data.request.family_members) {
            const count = data.request.family_members;
            famCountEl.textContent = count + ' ' + (count === 1 ? 'person' : 'people');
        }
        
        // Update special needs
        const needsEl = document.getElementById('successNeeds');
        if (needsEl && data.request.special_needs) {
            needsEl.innerHTML = '';
            data.request.special_needs.forEach(need => {
                const badge = document.createElement('span');
                badge.className = 'badge badge-blue';
                badge.textContent = capitalizeFirst(need);
                needsEl.appendChild(badge);
            });
        }
        
        // Update capacity bar
        const capBarFill = document.getElementById('capBarFill');
        const capBarLabel = document.getElementById('capBarLabel');
        if (capBarFill && data.center.occupancy_percentage) {
            const percentage = data.center.occupancy_percentage;
            capBarFill.style.width = percentage + '%';
            capBarFill.setAttribute('data-capacity', percentage);
            if (capBarLabel) {
                capBarLabel.textContent = percentage + '%';
            }
        }

        // Update call button with real phone number
        const btnCall = document.getElementById('btnCall');
        if (btnCall && data.center.contact_number) {
            btnCall.href = 'tel:' + data.center.contact_number;
            btnCall.textContent = 'Call Center (' + data.center.contact_number + ')';
        }

        // Update directions button with lat/lng
        const btnDir = document.getElementById('btnDirections');
        if (btnDir) {
            if (data.center.latitude) btnDir.setAttribute('data-lat', data.center.latitude);
            if (data.center.longitude) btnDir.setAttribute('data-lng', data.center.longitude);
        }

        // ── SMS notification badge ────────────────────────────────────────
        const smsNotice = document.getElementById('smsSentNotice');
        if (smsNotice) {
            smsNotice.style.display = 'flex';
        }
    }
    
    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // Submit another request button
    if (btnAnother) {
        btnAnother.addEventListener('click', function() {
            // Reset form
            evacuationSuccessScreen.style.display = 'none';
            evacuationFormScreen.style.display = 'block';
            
            // Reset selections
            selectedPriority = 'unaccompanied';
            selectedNeeds = ['wheelchair'];
            familyMembers = 1;
            famCount.textContent = '1';
            
            // Reset priority pills
            priorityGrid.querySelectorAll('.pri-pill').forEach(p => {
                p.classList.remove('active');
                p.setAttribute('aria-pressed', 'false');
            });
            priorityGrid.querySelector('[data-val="unaccompanied"]').classList.add('active');
            priorityGrid.querySelector('[data-val="unaccompanied"]').setAttribute('aria-pressed', 'true');
            
            // Reset needs pills
            needsGrid.querySelectorAll('.need-pill').forEach(p => {
                p.classList.remove('active');
                p.setAttribute('aria-pressed', 'false');
            });
            needsGrid.querySelector('[data-val="wheelchair"]').classList.add('active');
            needsGrid.querySelector('[data-val="wheelchair"]').setAttribute('aria-pressed', 'true');
            
            // Re-enable submit button
            btnRequestEvac.disabled = false;
            btnRequestEvac.textContent = 'Request Evacuation';
        });
    }
    
    // Close modal when clicking outside
    if (locationModal) {
        locationModal.addEventListener('click', function(e) {
            if (e.target === locationModal) {
                locationModal.style.display = 'none';
            }
        });
    }
});

// ── Post-submit: Wire up directions and call buttons ─────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Patch updateSuccessScreen to also update action buttons
    var origUpdate = typeof window.updateSuccessScreen === 'function' ? window.updateSuccessScreen : null;

    // Patch the directions button
    var btnDirections = document.getElementById('btnDirections');
    if (btnDirections) {
        btnDirections.addEventListener('click', function() {
            var lat = this.getAttribute('data-lat');
            var lng = this.getAttribute('data-lng');
            var centerName = document.querySelector('.center-name');
            var name = centerName ? encodeURIComponent(centerName.textContent) : 'Evacuation+Center';
            if (lat && lng) {
                window.open('https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng, '_blank');
            } else {
                window.open('https://www.google.com/maps/search/' + name, '_blank');
            }
        });
    }
});
