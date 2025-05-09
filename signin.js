document.getElementById('signinForm').addEventListener('submit', function(e) {
  let isValid = true;
  const identifier = document.getElementById('identifier');
  const password = document.getElementById('password');
  
  // Clear previous errors
  document.querySelectorAll('.text-danger').forEach(el => el.textContent = '');
  
  if (!identifier.value.trim()) {
      document.getElementById('identifierError').textContent = 'Email or username is required';
      isValid = false;
  }
  
  if (!password.value.trim()) {
      document.getElementById('passwordError').textContent = 'Password is required';
      isValid = false;
  }
  
  if (!isValid) {
      e.preventDefault();
  }
});