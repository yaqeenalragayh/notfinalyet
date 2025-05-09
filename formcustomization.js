document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('artworkForm');
    const colorOptions = document.querySelectorAll('.color-option');
    const originalArtworkImage = document.getElementById('originalArtworkImage');

    // Initial color selection
    if (colorOptions.length > 0) {
        colorOptions[0].classList.add('selected');
    }

    // Color selection functionality
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            const currentSelected = document.querySelector('.color-option.selected');
            if (currentSelected) {
                currentSelected.classList.remove('selected');
            }
            this.classList.add('selected');
            const colorNotesInput = document.getElementById('colorNotes');
            if (colorNotesInput) {
                colorNotesInput.value = window.getComputedStyle(this).backgroundColor;
            }
        });
    });

    // Parse URL parameters to pre-fill artwork details and image
    function getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            imageUrl: params.get('image') || 'default-artwork.jpg',
            dimensions: params.get('dimensions') || '24x36 inches',
            medium: params.get('medium') || 'Oil on canvas',
            year: params.get('year') || '2024',
            weight: params.get('weight') || '5.2kg',
            framing: params.get('framing') || 'Unframed',
            signature: params.get('signature') || 'Signed by artist'
        };
    }

    // Set original artwork details from URL parameters
    const artworkDetails = getUrlParams();
    originalArtworkImage.src = artworkDetails.imageUrl;
    originalArtworkImage.alt = `Original artwork: ${artworkDetails.medium}, ${artworkDetails.dimensions}`;
    
    document.getElementById('original-dimensions').textContent = artworkDetails.dimensions;
    document.getElementById('original-medium').textContent = artworkDetails.medium;
    document.getElementById('original-year').textContent = artworkDetails.year;
    document.getElementById('original-weight').textContent = artworkDetails.weight;
    document.getElementById('original-framing').textContent = artworkDetails.framing;
    document.getElementById('original-signature').textContent = artworkDetails.signature;

    // Auto-fill dimensions if they're in standard format (e.g., "24x36 inches")
    const dimensionMatch = artworkDetails.dimensions.match(/(\d+)\D+(\d+)/);
    if (dimensionMatch) {
        document.getElementById('width').value = dimensionMatch[1];
        document.getElementById('height').value = dimensionMatch[2];
    }

    // Auto-select medium if it matches an option
    const mediumMap = {
        'oil': 'Oil Painting',
        'acrylic': 'Acrylic Painting',
        'watercolor': 'Watercolor',
        'digital': 'Digital Art Print',
        'sculpture': 'Sculpture',
        'mixed': 'Mixed Media'
    };
    
    const mediumKey = artworkDetails.medium.toLowerCase().split(' ')[0];
    if (mediumMap[mediumKey]) {
        const artTypeSelect = document.getElementById('artType');
        for (let i = 0; i < artTypeSelect.options.length; i++) {
            if (artTypeSelect.options[i].text === mediumMap[mediumKey]) {
                artTypeSelect.selectedIndex = i;
                break;
            }
        }
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (form.checkValidity()) {
            // Collect form data
            const formData = {
                originalArtwork: artworkDetails,
                customization: {
                    medium: document.getElementById('artType').value,
                    dimensions: {
                        width: document.getElementById('width').value,
                        height: document.getElementById('height').value,
                        orientation: document.getElementById('orientation').value
                    },
                    colorScheme: document.querySelector('.color-option.selected') ? 
                        window.getComputedStyle(document.querySelector('.color-option.selected')).backgroundColor : '',
                    colorNotes: document.getElementById('colorNotes').value,
                    description: document.getElementById('artDescription').value,
                    referenceImages: document.getElementById('referenceUpload').files.length,
                    timeline: document.getElementById('timeline').value,
                    budget: document.getElementById('budget').value,
                    specialRequests: document.getElementById('specialRequests').value,
                    specialTechniques: {
                        texture: document.getElementById('texture').checked,
                        metallic: document.getElementById('metallic').checked,
                        glow: document.getElementById('glow').checked
                    }
                }
            };

            console.log('Custom artwork request submitted:', formData);

            // Show success message
            alert('Thank you for your custom artwork request! Our studio will contact you within 48 hours to discuss your vision.');
            form.reset();
            
            // Reset color selection
            if (colorOptions.length > 0) {
                colorOptions[0].click();
            }
        } else {
            // Show validation errors
            alert('Please fill in all required fields.');
        }
    });
    
    // Creative sentence on page load
    window.addEventListener('load', function() {
        const creativeSentences = [
            "Your custom artwork journey begins here...",
            "Transform inspiration into a masterpiece made just for you.",
            "Where original art meets your personal vision.",
            "Custom creations start with a conversation.",
            "Let's collaborate to make something uniquely yours."
        ];
        const randomIndex = Math.floor(Math.random() * creativeSentences.length);
        console.log(creativeSentences[randomIndex]);
    });
});