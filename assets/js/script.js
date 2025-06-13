// District data based on province selection
const districtData = {
    'Balochistan': ['Quetta', 'Pishin', 'Chaman', 'Zhob', 'Loralai', 'Ziarat', 'Sibi', 'Kalat', 'Khuzdar', 'Gwadar'],
    'Sindh': ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Mirpur Khas', 'Nawabshah', 'Thatta', 'Badin'],
    'Punjab': ['Dera Ghazi Khan', 'Multan', 'Bahawalpur', 'Rahim Yar Khan', 'Muzaffargarh', 'Layyah', 'Rajapur']
};

document.addEventListener('DOMContentLoaded', function() {
    // Province change event for districts
    const provinceSelect = document.getElementById('domicile_province');
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const districtSelect = document.getElementById('domicile_district');
            districtSelect.innerHTML = '';
            
            const selectedProvince = this.value;
            if (selectedProvince) {
                districtData[selectedProvince].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        });
    }
    
    // Campus validation based on domicile
    const campusSelect = document.getElementById('applied_campus');
    if (campusSelect && provinceSelect) {
        provinceSelect.addEventListener('change', validateCampus);
        campusSelect.addEventListener('change', validateCampus);
        
        function validateCampus() {
            const province = provinceSelect.value;
            const campus = campusSelect.value;
            
            if (province && campus) {
                if ((province === 'Balochistan' && campus !== 'Ziarat') || 
                    (province !== 'Balochistan' && campus === 'Ziarat')) {
                    alert('Error: Ziarat Campus is only for Balochistan students. Dera Ghazi Khan Campus is for Sindh and South Punjab students.');
                    campusSelect.value = province === 'Balochistan' ? 'Ziarat' : 'Dera Ghazi Khan';
                }
            }
        }
    }
    
    // Age validation
    const dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        dobInput.addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            if (age < 12 || age > 14) {
                alert('Student age must be between 12 to 14 years to be eligible for admission.');
                this.value = '';
            }
        });
    }
    
    // File upload validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size (500KB limit)
                if (file.size > 500 * 1024) {
                    alert('File size must be less than 500KB');
                    this.value = '';
                    return;
                }
                
                // Check file type (PDF only)
                if (!file.type.includes('pdf')) {
                    alert('Only PDF files are allowed');
                    this.value = '';
                }
            }
        });
    });
    
    // Income validation
    const incomeInput = document.getElementById('monthly_income');
    if (incomeInput) {
        incomeInput.addEventListener('change', function() {
            const income = parseInt(this.value) * 12; // Convert to annual
            if (income > 500000) {
                alert('Annual family income exceeds 500,000 PKR limit for eligibility');
                this.value = '';
            }
        });
    }
    
    // Result validation
    const resultInput = document.getElementById('last_school_result');
    if (resultInput) {
        resultInput.addEventListener('change', function() {
            const result = parseFloat(this.value);
            if (result < 75) {
                alert('Minimum 75% required in last class for eligibility');
                this.value = '';
            }
        });
    }
});