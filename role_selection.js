function handleSelection() {
    const isArtist = document.getElementById('artist').checked;
    const isEnthusiast = document.getElementById('enthusiast').checked;

    let selectedRoles = [];
    if (isArtist) {
        selectedRoles.push("artist");
    }
    if (isEnthusiast) {
        selectedRoles.push("enthusiast");
    }

    if (selectedRoles.length > 0) {
        alert("You have selected to continue as: " + selectedRoles.join(" and "));
        // In a real application, you would redirect the user
        // to different sections or personalize their experience
        // based on these roles.
        // Example: window.location.href = "/dashboard";
    } else {
        alert("Please select at least one role to continue.");
    }
}