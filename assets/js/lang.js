const langData = {
  hl: {
    nav_home: "Home", nav_listings: "Listings", nav_categories: "Categories",
    nav_about: "Hamare Baare Mein", nav_contact: "Contact Karo",
    nav_login: "Login Karo", nav_register: "Register Karo",
    hero_title: "Ranchi Mein Kuch Bhi Dhundo! 🔍",
    hero_sub: "Ranchi ke best restaurants, shops, doctors, aur bahut kuch — sab ek jagah",
    search_placeholder: "Kya dhundh rahe ho? (e.g. Restaurant, Doctor...)",
    search_btn: "Search Karo",
    tag_restaurant: "Restaurants", tag_doctor: "Doctors", tag_hotel: "Hotels",
    tag_school: "Schools", tag_gym: "Gyms", tag_salon: "Salons",
    sec_categories: "Categories Browse Karo", sec_recent: "Ranchi Mein Naye Listings",
    sec_popular: "Popular Areas", sec_how: "Kaise Kaam Karta Hai?",
    sec_testi: "Log Kya Kehte Hain", sec_biz: "Apna Business List Karo",
    how1_title: "Dhundo", how1_desc: "Category ya naam se apni zaroorat ki cheez search karo",
    how2_title: "Compare Karo", how2_desc: "Ratings, reviews aur details dekh ke best choose karo",
    how3_title: "Connect Karo", how3_desc: "Directly call karo ya directions lo — bilkul free!",
    ad_title: "Apna Business Ranchi Mein Famous Karo!",
    ad_desc: "HelloRanchi pe list karo aur hazaron customers tak pahuncho",
    ad_btn: "Free Mein List Karo", view_details: "Details Dekho",
    stat_listings: "Listings", stat_categories: "Categories",
    stat_users: "Happy Users", stat_areas: "Areas Covered",
    footer_about: "HelloRanchi — Ranchi ka #1 local business directory. Sab kuch ek jagah.",
    footer_quick: "Quick Links", footer_cats: "Top Categories",
    footer_contact: "Humse Milo", footer_copy: "© 2025 HelloRanchi. Sab rights reserved.",
  },
  hi: {
    nav_home: "होम", nav_listings: "लिस्टिंग", nav_categories: "श्रेणियाँ",
    nav_about: "हमारे बारे में", nav_contact: "संपर्क करें",
    nav_login: "लॉगिन करें", nav_register: "रजिस्टर करें",
    hero_title: "रांची में कुछ भी खोजें! 🔍",
    hero_sub: "रांची के बेहतरीन रेस्तरां, दुकानें, डॉक्टर और बहुत कुछ — सब एक जगह",
    search_placeholder: "क्या खोज रहे हैं? (जैसे रेस्तरां, डॉक्टर...)",
    search_btn: "खोजें",
    tag_restaurant: "रेस्तरां", tag_doctor: "डॉक्टर", tag_hotel: "होटल",
    tag_school: "स्कूल", tag_gym: "जिम", tag_salon: "सैलून",
    sec_categories: "श्रेणियाँ देखें", sec_recent: "रांची में नई लिस्टिंग",
    sec_popular: "लोकप्रिय क्षेत्र", sec_how: "यह कैसे काम करता है?",
    sec_testi: "लोग क्या कहते हैं", sec_biz: "अपना व्यवसाय सूचीबद्ध करें",
    how1_title: "खोजें", how1_desc: "श्रेणी या नाम से अपनी ज़रूरत की चीज़ खोजें",
    how2_title: "तुलना करें", how2_desc: "रेटिंग, समीक्षाएं और विवरण देखकर सर्वश्रेष्ठ चुनें",
    how3_title: "जुड़ें", how3_desc: "सीधे कॉल करें या दिशा-निर्देश लें — बिल्कुल मुफ़्त!",
    ad_title: "अपना व्यवसाय रांची में प्रसिद्ध करें!",
    ad_desc: "HelloRanchi पर सूचीबद्ध करें और हज़ारों ग्राहकों तक पहुँचें",
    ad_btn: "मुफ़्त में सूचीबद्ध करें", view_details: "विवरण देखें",
    stat_listings: "लिस्टिंग", stat_categories: "श्रेणियाँ",
    stat_users: "खुश उपयोगकर्ता", stat_areas: "क्षेत्र कवर",
    footer_about: "HelloRanchi — रांची की #1 स्थानीय व्यवसाय निर्देशिका। सब कुछ एक जगह।",
    footer_quick: "त्वरित लिंक", footer_cats: "शीर्ष श्रेणियाँ",
    footer_contact: "हमसे मिलें", footer_copy: "© 2025 HelloRanchi. सर्वाधिकार सुरक्षित।",
  }
};

// Exposed globally so inline onclick still works as fallback
window.setLang = function(lang) {
  localStorage.setItem('hr_lang', lang);

  // Translate all data-lang elements
  document.querySelectorAll('[data-lang]').forEach(el => {
    const key = el.getAttribute('data-lang');
    const val = langData[lang][key];
    if (val === undefined) return;
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
      el.placeholder = val;
    } else {
      el.textContent = val;
    }
  });

  // Update active button state
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-set') === lang);
  });

  // Update html lang attribute
  document.documentElement.lang = lang === 'hi' ? 'hi' : 'en';
};

// Apply saved language as soon as DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('hr_lang') || 'hl';
  window.setLang(saved);

  // Attach click listeners via delegation (works even if buttons render late)
  document.addEventListener('click', e => {
    const btn = e.target.closest('.lang-btn');
    if (btn && btn.dataset.set) {
      window.setLang(btn.dataset.set);
    }
  });
});
