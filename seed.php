<?php
require_once 'includes/db_connect.php';

$sql = "INSERT INTO `user_guide_content` (`section_key`, `title_en`, `title_mm`, `content_en`, `content_mm`) VALUES
('welcome', 'Welcome to Paicafe!', 'Paicafe မှ ကြိုဆိုပါတယ်!', 'Your guide to a seamless ordering experience, powered by <strong>Payvia</strong>.', 'Payvia မှ ပံ့ပိုးပေးထားသော လွယ်ကူချောမွေ့သော မှာယူမှုအတွေ့အကြုံအတွက် သင်၏လမ်းညွှန်'),
('loyalty', 'Your Account & Loyalty Points', 'သင့်အကောင့်နှင့် Loyalty Points', 'By creating an account, you join our loyalty program! For every <strong>100 Ks</strong> you spend, you earn <strong>1 point</strong>.', 'အကောင့်ဖွင့်ခြင်းဖြင့် ကျွန်ုပ်တို့၏ loyalty program တွင် ပါဝင်လိုက်ပါ။ သုံးစွဲသော <strong>100 ကျပ်</strong> တိုင်းအတွက် <strong>1 မှတ်</strong> ရရှိမည်ဖြစ်သည်။'),
('qr_guide', 'How to Order with QR Code', 'QR Code ဖြင့် မည်သို့မှာယူရမည်နည်း', 'Ordering directly from your table is easy. Just follow these simple steps:', 'သင့်စားပွဲမှ တိုက်ရိုက်မှာယူခြင်းသည် လွယ်ကူပါသည်။ ဤရိုးရှင်းသောအဆင့်များကို လိုက်နာပါ:'),
('feedback', 'Feedback & Support', 'အကြံပြုချက်နှင့် ပံ့ပိုးမှု', 'Your feedback is important to us! If you have any suggestions or comments about our service or this system, please let us know via the contact details in the footer.', 'သင်၏အကြံပြုချက်သည် ကျွန်ုပ်တို့အတွက် အရေးကြီးပါသည်။ ကျွန်ုပ်တို့၏ဝန်ဆောင်မှု သို့မဟုတ် ဤစနစ်နှင့်ပတ်သက်၍ အကြံပြုချက်များ သို့မဟုတ် မှတ်ချက်များရှိပါက footer ရှိ ဆက်သွယ်ရန်အသေးစိတ်အချက်အလက်များမှတစ်ဆင့် ကျွန်ုပ်တို့အား အသိပေးပါ။')";

if ($conn->query($sql) === TRUE) {
    echo "✅ Seed data inserted successfully";
} else {
    echo "❌ Error: " . $conn->error;
}
?>