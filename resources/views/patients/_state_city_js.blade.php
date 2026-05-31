<script>
(function () {
const INDIA = {
  "Andhra Pradesh": ["Adoni","Amaravati","Anantapur","Bhimavaram","Chilakaluripet","Chittoor","Eluru","Gooty","Guntur","Hindupur","Jaganmohan Reddy Colony","Kadapa","Kakinada","Kurnool","Machilipatnam","Madanapalle","Markapur","Nandyal","Narasaraopet","Nellore","Ongole","Proddatur","Rajahmundry","Srikakulam","Tadipatri","Tenali","Tirupati","Vijayawada","Visakhapatnam","Vizianagaram"],
  "Arunachal Pradesh": ["Along","Aalo","Bomdila","Changlang","Daporijo","Itanagar","Khonsa","Naharlagun","Pasighat","Roing","Seppa","Tawang","Tezu","Yingkiong","Ziro"],
  "Assam": ["Barpeta","Bongaigaon","Dhubri","Dibrugarh","Diphu","Goalpara","Golaghat","Guwahati","Haflong","Jorhat","Karimganj","Kokrajhar","Mangaldoi","Nagaon","Nalbari","North Lakhimpur","Sibsagar","Silchar","Sivasagar","Tezpur","Tinsukia"],
  "Bihar": ["Araria","Arrah","Aurangabad","Bagaha","Begusarai","Bettiah","Bhagalpur","Bihar Sharif","Buxar","Chapra","Darbhanga","Dehri","Danapur","Gaya","Hajipur","Jahanabad","Katihar","Kishanganj","Madhubani","Motihari","Munger","Muzaffarpur","Nawada","Patna","Purnia","Saharsa","Samastipur","Sasaram","Sitamarhi","Siwan"],
  "Chhattisgarh": ["Ambikapur","Bhatapara","Bhilai","Bilaspur","Chirmiri","Dalli-Rajhara","Dhamtari","Durg","Jagdalpur","Korba","Mahasamund","Rajnandgaon","Raigarh","Raipur"],
  "Goa": ["Bicholim","Curchorem","Mapusa","Margao","Panaji","Pernem","Ponda","Quepem","Sanquelim","Vasco da Gama"],
  "Gujarat": ["Ahmedabad","Amreli","Anand","Ankleshwar","Bharuch","Bhavnagar","Botad","Dahod","Gandhinagar","Gandhidham","Godhra","Gondal","Jamnagar","Junagadh","Kalol","Keshod","Khambhat","Khandla","Mehsana","Morbi","Nadiad","Navsari","Palanpur","Porbandar","Rajkot","Surat","Surendranagar","Vadodara","Vapi","Veraval"],
  "Haryana": ["Ambala","Bahadurgarh","Bhiwani","Faridabad","Fatehabad","Gohana","Gurgaon","Hansi","Hisar","Jind","Kaithal","Karnal","Kurukshetra","Mahendragarh","Narnaul","Palwal","Panchkula","Panipat","Rewari","Rohtak","Sirsa","Sonipat","Thanesar","Yamunanagar"],
  "Himachal Pradesh": ["Baddi","Bilaspur","Chamba","Dharamsala","Hamirpur","Kangra","Kullu","Mandi","Mussoorie","Nahan","Palampur","Paonta Sahib","Rampur","Shimla","Solan","Sundarnagar","Una"],
  "Jharkhand": ["Bokaro Steel City","Chaibasa","Chakradharpur","Chirkunda","Deoghar","Dhanbad","Dumka","Giridih","Godda","Hazaribagh","Jamshedpur","Khunti","Lohardaga","Medininagar","Phusro","Ramgarh","Ranchi","Sahibganj","Simdega"],
  "Karnataka": ["Bagalkot","Ballari","Belagavi","Bengaluru","Bidar","Chikkamagaluru","Chitradurga","Davanagere","Gadag-Betageri","Hassan","Hubli-Dharwad","Kalaburagi","Kodagu","Kolar","Koppal","Madikeri","Mandya","Mangaluru","Mysuru","Raichur","Robertsonpet","Shivamogga","Tumakuru","Udupi","Vijayapura","Yadgir"],
  "Kerala": ["Alappuzha","Attingal","Irinjalakuda","Kalpetta","Kannur","Kasaragod","Kayamkulam","Kochi","Kollam","Kozhikode","Kottayam","Malappuram","Manjeri","Nedumangad","Palakkad","Pathanamthitta","Ponnani","Punalur","Thalassery","Thiruvananthapuram","Thrissur","Tirur","Vatakara","Wayanad"],
  "Madhya Pradesh": ["Betul","Bhind","Bhopal","Burhanpur","Chhindwara","Chhatarpur","Damoh","Datia","Dewas","Guna","Gwalior","Indore","Itarsi","Jabalpur","Katni","Khandwa","Khargone","Mandsaur","Murwara","Nagda","Neemuch","Pithampur","Ratlam","Rewa","Sagar","Satna","Sehore","Shivpuri","Singrauli","Ujjain","Vidisha"],
  "Maharashtra": ["Ahmednagar","Akola","Amravati","Aurangabad","Baramati","Beed","Bhiwandi","Buldhana","Chandrapur","Chhatrapati Sambhajinagar","Dhule","Gondia","Ichalkaranji","Jalgaon","Jalna","Kalyan-Dombivli","Kolhapur","Latur","Malegaon","Mira-Bhayandar","Mumbai","Nagpur","Nanded","Nashik","Navi Mumbai","Osmanabad","Parbhani","Panvel","Pune","Sangli","Shirdi","Solapur","Thane","Ulhasnagar","Vasai-Virar","Wardha","Yavatmal"],
  "Manipur": ["Bishnupur","Chandel","Churachandpur","Imphal","Jiribam","Senapati","Tamenglong","Thoubal","Ukhrul"],
  "Meghalaya": ["Baghmara","Jowai","Khliehriat","Nongpoh","Nongstoin","Resubelpara","Shillong","Tura","Williamnagar"],
  "Mizoram": ["Aizawl","Champhai","Khawzawl","Kolasib","Lawngtlai","Lunglei","Mamit","Saiha","Serchhip"],
  "Nagaland": ["Dimapur","Kiphire","Kohima","Longleng","Mokokchung","Mon","Phek","Tuensang","Wokha","Zunheboto"],
  "Odisha": ["Angul","Balasore","Baripada","Bargarh","Bhadrak","Bhubaneswar","Brahmapur","Cuttack","Dhenkanal","Jharsuguda","Jeypore","Kendujhar","Koraput","Paradip","Phulbani","Puri","Rayagada","Rourkela","Sambalpur","Sundargarh"],
  "Punjab": ["Abohar","Amritsar","Barnala","Batala","Bathinda","Faridkot","Firozpur","Gurdaspur","Hoshiarpur","Jalandhar","Kapurthala","Khanna","Ludhiana","Malerkotla","Moga","Mohali","Muktsar","Pathankot","Patiala","Phagwara","Rupnagar","Sangrur","Zirakpur"],
  "Rajasthan": ["Ajmer","Alwar","Baran","Barmer","Banswara","Bharatpur","Bhilwara","Bikaner","Bundi","Chittorgarh","Churu","Dausa","Dholpur","Dungarpur","Hanumangarh","Jaipur","Jaisalmer","Jalore","Jhalawar","Jhunjhunu","Jodhpur","Karauli","Kota","Nagaur","Pali","Pratapgarh","Rajsamand","Sawai Madhopur","Sikar","Sirohi","Sri Ganganagar","Tonk","Udaipur"],
  "Sikkim": ["Gangtok","Gyalshing","Jorethang","Mangan","Namchi","Nayabazar","Rangpo","Singtam"],
  "Tamil Nadu": ["Ambattur","Avadi","Chennai","Coimbatore","Cuddalore","Dindigul","Erode","Hosur","Kanchipuram","Karur","Kumbakonam","Madurai","Nagapattinam","Nagercoil","Namakkal","Pudukkottai","Rajapalayam","Ranipet","Salem","Sivakasi","Tambaram","Thanjavur","Tiruchirappalli","Tirunelveli","Tiruppur","Tiruvottiyur","Tiruvannamalai","Udhagamandalam","Vellore","Viluppuram"],
  "Telangana": ["Adilabad","Bhongir","Bodhan","Hyderabad","Jagtial","Karimnagar","Khammam","Mahbubnagar","Mancherial","Medak","Miryalaguda","Nalgonda","Nizamabad","Ramagundam","Sangareddy","Siddipet","Suryapet","Vikarabad","Wanaparthy","Warangal"],
  "Tripura": ["Agartala","Ambassa","Belonia","Dharmanagar","Kailashahar","Khowai","Melaghar","Sabroom","Teliamura","Udaipur"],
  "Uttar Pradesh": ["Agra","Aligarh","Amroha","Bahraich","Banda","Bareilly","Barabanki","Bulandshahr","Etawah","Farrukhabad","Fatehpur","Firozabad","Ghaziabad","Gonda","Gorakhpur","Hapur","Hardoi","Jhansi","Jaunpur","Kanpur","Khurja","Lakhimpur","Loni","Lucknow","Mathura","Mau","Meerut","Mirzapur","Modinagar","Moradabad","Muzaffarnagar","Noida","Orai","Prayagraj","Raebareli","Rampur","Saharanpur","Sambhal","Shahjahanpur","Sitapur","Unnao","Varanasi"],
  "Uttarakhand": ["Almora","Bageshwar","Champawat","Chamoli","Dehradun","Haridwar","Haldwani","Kashipur","Kotdwar","Mussoorie","Nainital","Pauri","Pithoragarh","Ramnagar","Rishikesh","Roorkee","Rudrapur","Srinagar (Uttarakhand)","Tehri","Uttarkashi"],
  "West Bengal": ["Asansol","Balurghat","Bangaon","Bankura","Baharampur","Bardhaman","Bolpur","Cooch Behar","Dankuni","Durgapur","English Bazar","Gangarampur","Habra","Haldia","Ingraj Bazar","Islampur","Jalpaiguri","Jhargram","Kharagpur","Kolkata","Krishnanagar","Malda","Medinipur","Nabadwip","Raiganj","Ranaghat","Shantipur","Siliguri"],
  "Andaman and Nicobar Islands": ["Bamboo Flat","Car Nicobar","Diglipur","Havelock","Hutbay","Mayabunder","Neil Island","Port Blair","Rangat"],
  "Chandigarh": ["Chandigarh","Manimajra"],
  "Dadra and Nagar Haveli and Daman and Diu": ["Amli","Daman","Diu","Silvassa"],
  "Delhi": ["Connaught Place","Dwarka","East Delhi","Greater Kailash","Janakpuri","Karol Bagh","Lajpat Nagar","Mayur Vihar","Nehru Place","New Delhi","North Delhi","Pitampura","Preet Vihar","Rohini","Saket","Shahdara","South Delhi","Vasant Kunj","West Delhi"],
  "Jammu and Kashmir": ["Anantnag","Baramulla","Bandipora","Budgam","Doda","Ganderbal","Handwara","Jammu","Kathua","Kishtwar","Kulgam","Kupwara","Poonch","Pulwama","Rajouri","Ramban","Reasi","Shopian","Sopore","Srinagar","Udhampur"],
  "Ladakh": ["Diskit","Kargil","Leh","Padum","Zanskar"],
  "Lakshadweep": ["Agatti","Amini","Androth","Kavaratti","Minicoy"],
  "Puducherry": ["Karaikal","Mahe","Ozhur","Puducherry","Villianur","Yanam"]
};

const stateEl = document.getElementById('state-select');
const cityEl  = document.getElementById('city-select');
const SELECTED_STATE = @json($selectedState ?? '');
const SELECTED_CITY  = @json($selectedCity ?? '');

// Populate states
Object.keys(INDIA).sort().forEach(st => {
    const o = new Option(st, st);
    if (st === SELECTED_STATE) o.selected = true;
    stateEl.appendChild(o);
});

function populateCities(state, selectCity) {
    $(cityEl).val(null).trigger('change');
    cityEl.innerHTML = '';
    if (!state || !INDIA[state]) {
        cityEl.appendChild(new Option('— Select State first —', ''));
        cityEl.disabled = true;
        $(cityEl).trigger('change');
        return;
    }
    cityEl.appendChild(new Option('— Select City —', ''));
    INDIA[state].forEach(city => {
        const o = new Option(city, city);
        if (city === selectCity) o.selected = true;
        cityEl.appendChild(o);
    });
    cityEl.appendChild(new Option('Other / Not Listed', '__other__'));
    cityEl.disabled = false;
    $(cityEl).trigger('change');
}

// Re-init Select2 after dynamic change
$(stateEl).select2({ theme: 'classic', placeholder: '— Select State / UT —' });
$(cityEl).select2({ theme: 'classic', placeholder: '— Select City —' });

$(stateEl).on('change', function () {
    populateCities(this.value, '');
});

// On load: pre-populate if editing
if (SELECTED_STATE) {
    populateCities(SELECTED_STATE, SELECTED_CITY);
}
})();
</script>
