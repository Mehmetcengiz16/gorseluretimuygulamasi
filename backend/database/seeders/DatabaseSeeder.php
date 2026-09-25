<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\CreditTransactionType;
use App\Models\Admin;
use App\Models\Category;
use App\Models\LightingPreset;
use App\Models\QualityLevel;
use App\Models\SceneType;
use App\Models\StudioStyle;
use App\Models\Template;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Database\Seeder;

/**
 * İçerik, ekran tasarımlarındaki (ekrantasarimlari/) metin ve görsellerle birebir aynıdır.
 */
class DatabaseSeeder extends Seeder
{
    private const IMG = 'https://lh3.googleusercontent.com/aida-public/';

    public function run(): void
    {
        Admin::updateOrCreate(['email' => 'admin@studioai.test'], [
            'name' => 'Süper Admin',
            'password' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $categories = $this->categories();
        $styles = $this->studioStyles();
        $scenes = $this->sceneTypes();
        $lights = $this->lightingPresets();
        $this->qualityLevels();
        $this->templates($categories, $styles, $scenes, $lights);

        $demo = User::firstOrCreate(['email' => 'demo@studioai.test'], [
            'name' => 'Demo Kullanıcı',
            'password' => 'password',
            'pro_expires_at' => now()->addYear(),
        ]);
        if ($demo->wasRecentlyCreated) {
            app(CreditService::class)->credit($demo, 100, CreditTransactionType::SignupBonus, null, 'Demo hesap kredisi');
        }
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $rows = [
            ['kozmetik', 'Kozmetik', 'spa'],
            ['parfum-cam', 'Parfüm & Cam', 'fragrance'],
            ['sneaker-moda', 'Sneaker & Moda', 'apparel'],
            ['elektronik', 'Elektronik', 'devices'],
            ['taki-saat', 'Takı & Saat', 'diamond'],
            ['organik', 'Organik', 'eco'],
            ['cilt-bakimi', 'Cilt Bakımı', 'face'],
            ['icecek', 'İçecek', 'local_drink'],
        ];

        $out = [];
        foreach ($rows as $i => [$slug, $name, $icon]) {
            $out[$slug] = Category::updateOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'sort_order' => $i]);
        }

        return $out;
    }

    /** @return array<string, StudioStyle> */
    private function studioStyles(): array
    {
        $rows = [
            ['ham', 'Yok / Ham', null, null],
            ['luks-ticari', 'Lüks Ticari', 'AB6AXuAk2IFkmAPNM11CoZkXNmPbhS6HuQRA7sljGWNxHDzmI7AuIogI5orYjTwbFTF9eQajLRm62FH971CDRcQbzH0a4AaB1z22ztRcjnzGGpmYL56I2C2BZlltC2URhaCN1KkL7E-fW4AN_7I03EkR_p1uGyAcbCySz2t3gtx7NxZHx_jra-k5bIyYrjfNH1Fjmqd8h7rDySst7SB3k-BZo79cX1Zkw8D4OsyAYwECa1r2O4idIRkA2s33Ag',
                'luxury commercial advertising look, reflective black obsidian marble, warm amber rim light, fine mist, volumetric golden god rays'],
            ['minimalist', 'Minimalist', 'AB6AXuB8pB_xhj-6T7HV-ZOgGwsCMutdC-GfTCjkfTTVwRCHZcZEnaWLPeHfhvNczy3B5g7A5s_qaB8esqHEC1l2lvpZnKjKjRAMUD65om2XsfXCPHIHhuCMZiv7GtHKTmm6cAGovHZpDDyxLSF3d03le3eA4-SjX2it1Y_v8dhiO8h_IS3JvtA5VgLcnBHtULOu8_3f_2gCHaXiaAzCoXDcMfnjQMiEm1P3r2MW2zgzHPJQM4S4SwAf68SNdg',
                'minimalist Scandinavian aesthetic, muted sandstone pedestal, warm diffuse daylight, dried pampas grass shadows, clean beige and stone tones, high key'],
            ['cyber-neon', 'Cyber Neon', 'AB6AXuBjbsZzTMHAq5BkOudKVCcGttMNcDnWsyjh3QuKHpQiOrL51rVx11e7Ud91RxCNdAfZp2WRXvnDdDB4ChwkIQZDk-dn6fsfrWaz9P4hi8_-Io5vMn6gLQp33eRopBuqoqKo9ui2frckMxAg-AqO4HfGFPkPHJsB33eGzEUcyPwUKB76MTzY2qWEI-o2krFWS4-881b68RCtJrQhxMDuhZoVGtCS227sD6VSGu_1540R-cPgf5i0VJtUAw',
                'futuristic cyberpunk set, neon cyan and warm gold laser accents, dark reflective smoked glass floor, volumetric smoke'],
            ['botanical', 'Botanical', 'AB6AXuDbuBUbJnDL-awr4o1PFLq0elT5e0svFzxzREfT4gfL0293mxsyQeJm173jg9qisbx5X3-NrjyKX1TMt-BqHb5L21cEw9_5jHHHN_ovClQrR7rfFU5Siz1qbCIhB6e8gIKwDC9-9Qd1UnGUiFXVDBZhGG4JcTTaWFMCLaNno4A_l-NndNap25kt36Ivpc2CpbMECDY8YhiJK1q4aiVCABDYKVdQEi4-ka58aQkAGeciAewZA4lihiQf5w',
                'organic botanical scene, textured mossy stone, dewy green monstera leaves, dappled golden morning sunlight, earthy tones'],
        ];

        $out = [];
        foreach ($rows as $i => [$slug, $name, $img, $prompt]) {
            $out[$slug] = StudioStyle::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'thumbnail_path' => $img ? self::IMG.$img : null,
                'prompt_fragment' => $prompt,
                'is_pro' => $slug === 'cyber-neon',
                'sort_order' => $i,
            ]);
        }

        return $out;
    }

    /** @return array<string, SceneType> */
    private function sceneTypes(): array
    {
        $rows = [
            ['podyum-uzeri', 'Podyum Üzeri', 'AB6AXuBzVNzZiVpcmXDCNxkU62l3MeLGEbuaIQK4ukupd7ZkCn_rrE48Gi2uIMndodnyjR2KipFbYlP0Lr6RYF4qR_QwBb_dmGyZVfemcP4jtV0UnQNzLlMqb55kpIEHzV8KXBgy1Kt4LPz2HQfy2AsKekwmcQL9A-YbJDAHdBt0fvMNHWn8LADNDgzrzPZ73FkxBdGKFW53pYGqb-OC_xD2TA5Hpm23Le_U0QCvZ7qupZLPxxJ7at9yTwHfZQ',
                'product standing on a minimalist round podium, clean product placement stage'],
            ['havada-asili', 'Havada Asılı', 'AB6AXuCdPxzv3eY65mkoA3C_w4JPq6-TvEG_nKkc24tGKW3Qsbp2RbCNANxvwil6XP3gqLgXFvq9wY8HLOIcYSOjxT5mVSd42_Vpk17xBsWt00k1WcmVwhw7VbuM5vtWB1A7A5va3d3APo03M9CZBFZhEOqyjieTfwLicLp5WYvvmSU7d4ayO7dEAo2fQOreGtJBAHigvwqAb_yTK50YqRzAV3inL3aOKQW0mNkM_O3gwtu5L_ZLufv90P7wsA',
                'product floating in zero gravity with delicate levitating water droplets and subtle golden dust particles'],
            ['dogal-tas-su', 'Doğal Taş & Su', 'AB6AXuCEvUkV5krwZo1-UpMPjMzrqGnNsfJw6dH4I6n7rHUaynmh5doBWcTSaz31ZKu7VzC_AWCedJWNwEj7RX2qeTdiDffPjqfgsYiBmnkaKSjJw-txwiZmx6PxOANXhG7cFGXxAAjRkqBL55bcJiDQLBwcBFlbql2xbGIp71ugu-A2_LANd7pjn_y1OuIjKAdi19Hj67uklgNfLNgJQsUUJ1Gs138lZ0vzaCQcogXHZf9FKNI6dSUea3d4xQ'
                , 'product placed on natural wet black volcanic stones surrounded by a crystal clear rippling water pool, spa atmosphere'],
            ['studyo-masasi', 'Stüdyo Masası', 'AB6AXuCE7wYZtuW5BMZIyGbhi1FFTH6daWKHZZSfjnpsmAVPZ1nG8g62OOE6eSJ6TAIBX2HQnRrHf9ODjBsz26IxmfztnVesr_lzg9OXV4MWbUppcWYka9hbDpSH_PDIQ9HZ03fVwY7lBqNPSHJzQTEuw6Zdv-poyB3KIw0_MwKg2bnTcdfelUBWmjfPVqOHhniIJ0FOomPv7XuGKkb_zz5QPgECVJ05UK_G-WVWeUSQtybf_ydrcnoyyyB8sA',
                'product on a clean wooden designer studio desk, softly defocused interior backdrop'],
            ['yasam-alani', 'Yaşam Alanı', 'AB6AXuARTVkG_fwePjqeO2vYzmndbzvxKJeaSWMadLuknYtqhZ1sQCkknADJ8zrqkTUKixKQQZDOr3-COXnaHL109aHay50u2DlGZirlPeJMTB65MgLO6LvsnPpXwiBsk2EbMiPTIOJ7o3A8OCZdmGcmlTnS98IyAZBgfIPITkSDc1AyhjoUJz_WFbOK_XG1qKQoJ66bS0UfS6T2yfu4qKS_ZfT2CvnCffzRkr25F6YfnlER4XD3fDYipF0lyA',
                'product on a marble countertop in a modern penthouse living room, sunlight through sheer curtains in the background'],
        ];

        $out = [];
        foreach ($rows as $i => [$slug, $name, $img, $prompt]) {
            $out[$slug] = SceneType::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'thumbnail_path' => self::IMG.$img,
                'prompt_fragment' => $prompt,
                'sort_order' => $i,
            ]);
        }

        return $out;
    }

    /** @return array<string, LightingPreset> */
    private function lightingPresets(): array
    {
        $rows = [
            ['softbox', 'Softbox', 'Yumuşak & Eşit', 'wb_twilight', 'large softbox lighting, soft even illumination, gentle shadows'],
            ['spot', 'Spot Işık', 'Dramatik Kontrast', 'highlight', 'single dramatic spotlight, high contrast chiaroscuro, deep shadows'],
            ['altin-saat', 'Altın Saat', 'Sıcak Güneş Halesi', 'wb_sunny', 'warm golden hour sunlight, glowing sun halo, long soft shadows'],
            ['arkadan', 'Arkadan Işık', 'Rim Işıltısı & Ayrım', 'flare', 'strong backlight rim lighting, glowing edges separating the product from the background'],
        ];

        $out = [];
        foreach ($rows as $i => [$slug, $name, $subtitle, $icon, $prompt]) {
            $out[$slug] = LightingPreset::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'subtitle' => $subtitle, 'icon' => $icon, 'prompt_fragment' => $prompt, 'sort_order' => $i,
            ]);
        }

        return $out;
    }

    private function qualityLevels(): void
    {
        $rows = [
            ['draft', 'Taslak (Düşük)', '1K', 'Quick draft quality'],
            ['balanced', 'Dengeli', '2K', 'High quality commercial photograph, crisp details'],
            ['ultra', 'Ultra 4K', '4K', 'Ultra high resolution 4K commercial photograph, hyper-detailed textures, razor sharp'],
        ];

        foreach ($rows as $i => [$key, $name, $size, $prompt]) {
            QualityLevel::updateOrCreate(['key' => $key], [
                'name' => $name, 'image_size' => $size, 'credit_multiplier' => 1, 'prompt_fragment' => $prompt, 'sort_order' => $i,
            ]);
        }
    }

    private function templates(array $cat, array $styles, array $scenes, array $lights): void
    {
        $rows = [
            ['Lüks Mermer & Altın Işık', 'Pürüzsüz stüdyo yansımaları', 'kozmetik', 'pro', 2400, 'luks-ticari', 'podyum-uzeri', 'arkadan',
                'AB6AXuCbE_Up7_KI3nRCuhEpA4ltBJ5RS5L-q5h9giOmiEdpD08mmuIZVIZ8Dqq275vT_Onnc3-V8vakVTDkXAjKBQ64qv1pIK08p8aHgNm_LTBNLBgEAIUT0ecaZQTFMP0cZDutbVnLouxdsSCKZKyafR1h7s0Y5ZI7iRXlL3uFrzdVB1yKCuSuaMcJ8yT0XLr45P7OUWb4xYg9ur1dZzI1L5oLbHJkVTnnoVpApf45k-UUV2JefeuOKdIXGg',
                'Parlatılmış beyaz Carrara mermer kaide, ince altın damarlar, yumuşak amber rim ışığı, uçuşan altın toz partikülleri.'],
            ['Podium & Neon Cyber', 'Fütüristik teknoloji sahnesi', 'sneaker-moda', 'trend', 1800, 'cyber-neon', 'podyum-uzeri', 'spot',
                'AB6AXuBItOo1AAWZT9aTWTzVjtXBKOl3kx6LB4c8OQdERnAbX4g6JTzAXgrJHVpPEGsAB7KCGiTNZTWG-4qWsJsk3f9GfdEGiEtn2aH5IbtrZAUnneEW-CoxIjT3ut4VWKcriv2KLRGDHbzdRDOr_Oei2I3cAVU1Jc37HdVmfcZmGUrrz-LVB28wOl5RbxV6JjaA3tYlsFHigESYl0Q2OS7brVil3Zj4N7UORxcFGm1p14e9Zw98ca43z6Lerg',
                'Parlayan siberpunk podyum, elektrik amber ve neon camgöbeği vurgular, hacimli duman, metalik yansımalar.'],
            ['Doğal Gün Işığı & Ahşap', 'Sıcak organik ürün çekimi', 'organik', null, 940, 'botanical', 'studyo-masasi', 'altin-saat',
                'AB6AXuB71iO5oydW6otVIG9PwRimk1KijrMC1YXLRb5SbhP14z4121aQZHziBjfBIzHbFTzPWGicVLuaM9CrrDuFC5YGruAMABJQze5hb_Dmd0W67iYujglD523qFRS1fXGBwhw7TsNkMuckbsJbHtGOvolR9lrOuSd9XK9wP39ogHOkNglc-wW4LsEyp14-5_5HTJHGgT2QJ0S2Crlmvvd8nEoCTA4_p6vJ69yv5U-rDXdY9uwkG3_7j0Pqjg',
                'Eskitilmiş koyu meşe masa, sabah pencere ışığı, zeytin yapraklarının yumuşak gölgeleri, sıcak toprak tonları.'],
            ['Minimal Pastel Gölge', 'Şişe & kavanoz ürünleri', 'cilt-bakimi', null, 1200, 'minimalist', 'podyum-uzeri', 'softbox',
                'AB6AXuBLSMc5gSslbIJ_AFBr9IiuyrAZ3w77kddKi0q-SxsHVt_C7l4dDAMGrOfCvAGR_a_7kWv50tvp5E9Nt659GUbof-LYOMy1KHz7EEu8TPn46IHoiuXPbB0ED3o_UAekfFlpNmHGWLG6Y8jTmUGsRMcixh2xiPRk8erdMmrJbZZRacLZOwZiwGLoqbqFW4axwXYXfv11yImlCwdjThdVNtas7zSdm0C-Y2_o_mQBOd7JzvCZQWVa-KPYFg',
                'Pürüzsüz pastel geometrik bloklar, keskin açılı gölgeler, ultra temiz stüdyo ışığı, bej ve kömür kontrastı.'],
            ['Su Damlaları & Buz', 'Ferahlatıcı soğuk efekt', 'icecek', null, 3100, 'luks-ticari', 'havada-asili', 'arkadan',
                'AB6AXuAlsozKDyAJ7GQXxNaHk3weU3FN1SUv5ZPAU1kQ3M4eAeONEXTElnhFClWBhrGmDcIo_d8hmOmuFhI4pXX_OIyYQzdK72XgNiCRlyEySnr2_w5xIqDRPtz2a2RAfrArAubtBLmWvirS3plNAY9g36pcYhO021cne1ATQ4hICRcjwaWJexpblGirDHvDCnn4V7D1QgHO3ESvYKSWMNU3CCay6Ffgyz9Viact8BbAkILhmG7fqm4jxFn0JQ',
                'Kristal buz blokları, havada asılı dinamik su sıçraması, yüksek hızlı flaş, koyu atmosferik zemin, arkadan ışık.'],
            ['Dramatik Sinematik Işık', 'Saat & mücevherat', 'taki-saat', 'pro', 2700, 'luks-ticari', 'dogal-tas-su', 'spot',
                'AB6AXuCwbKazqUWHCUv4gFHEPYkArYBZ4EDwoOhtce9AUwebB_mNYfAjrcPw0_0UZveyr6RyoapU-mZ7fGrFPaAX_nHO1nw0i24ei1R0xzS41DXLG3LJv85L6-Zo2Tvjwv0KGKgmRnLhfNJdjRpuRl_cR90DQCUTi0F5rKdfhLPvvzLvIyTY4zxpG6BC3oXoPLXJnfriyulWTJrWNtEnhJ2ZLsRuB_JpPoOxG0lX2n6voNisbAvwCrb6C54vGg',
                'Koyu fırçalanmış arduvaz taş, yüksek kontrastlı chiaroscuro ışık, altın detaylarda keskin yansımalar, sinematik amber sıcaklık.'],
        ];

        foreach ($rows as $i => [$title, $subtitle, $catSlug, $badge, $likes, $style, $scene, $light, $img, $prompt]) {
            Template::updateOrCreate(['title' => $title], [
                'subtitle' => $subtitle,
                'category_id' => $cat[$catSlug]->id,
                'badge' => $badge,
                'is_pro' => $badge === 'pro',
                'is_featured' => true,
                'likes_count' => $likes,
                'studio_style_id' => $styles[$style]->id,
                'scene_type_id' => $scenes[$scene]->id,
                'lighting_preset_id' => $lights[$light]->id,
                'default_prompt' => $prompt,
                'quality_key' => 'ultra',
                'cover_path' => self::IMG.$img,
                'sort_order' => $i,
            ]);
        }
    }
}
