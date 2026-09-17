# Image sources and licences

Registry of every file in the initial media library (`database/seeders/media/`).
The machine-readable source of truth is `manifest.json` in the same folder;
this table is the human summary. Update both when a file is added, replaced
or removed.

**Rules**

- All files here are licensed stock illustrations from Pexels, published
  under the [Pexels License](https://www.pexels.com/license/): free for
  commercial use, modification allowed, no attribution required.
  Attribution is recorded anyway so the origin of every file stays known.
- They are used only as service illustrations, the homepage hero and
  generic supporting visuals. **They are never attached to projects,
  case studies or before/after evidence** - those sections show only
  photographs of the company's own work, uploaded by the team from
  Filament. The project media picker hides this library for that reason.
- Files are downloaded and stored locally (`storage/app/public/media/library/`)
  by `Database\Seeders\InitialMediaSeeder` - nothing hotlinks to Pexels.
- Alt text below is the default written at import; editors may change it
  in *مكتبة الوسائط* and the seeder never overwrites their edit.
- Every file is resized to at most 1800 px on the long edge and saved as
  progressive JPEG (quality 82) with EXIF stripped.

**Re-import after a fresh install**

```bash
php artisan db:seed --class=InitialMediaSeeder
```

(`php artisan migrate:fresh --seed` runs it too via `DatabaseSeeder`.)

| File | Role | Photographer | Pexels photo | Default alt text (ar) |
|---|---|---|---|---|
| `home-hero.jpg` | hero | Tima Miroshnichenko | [6197108](https://www.pexels.com/photo/6197108/) | فريق تنظيف بزي موحد ينظف أرضية ونوافذ مطبخ حديث بجدران رخامية |
| `villa-cleaning.jpg` | service | Ubaid Awan | [12122335](https://www.pexels.com/photo/12122335/) | واجهة فيلا حديثة بيضاء بأبواب جراج مغلقة |
| `apartment-cleaning.jpg` | service | Wilcle Nunes | [27164969](https://www.pexels.com/photo/27164969/) | غرفة معيشة حديثة بأثاث فاتح وجدار رخامي |
| `upholstery-carpet-cleaning.jpg` | service | Jesus Arias | [4401538](https://www.pexels.com/photo/4401538/) | يد تنظّف مقعد كنبة قماشية بفوهة مكنسة |
| `office-cleaning.jpg` | service | Max Vakhtbovych | [7750129](https://www.pexels.com/photo/7750129/) | مكتب حديث بمكاتب بيضاء ونوافذ واسعة |
| `post-construction-cleaning.jpg` | service | La Miko | [3616746](https://www.pexels.com/photo/3616746/) | مكنسة صناعية ومكنسة يدوية في غرفة بعد أعمال التشطيب |
| `glass-facade-cleaning.jpg` | service | Blissful Place Cleaning company in Perth | [31435403](https://www.pexels.com/photo/31435403/) | ممسحة مطاطية وقفاز أصفر يمسحان لوح زجاج مبلل |
| `kitchen-bathroom-cleaning.jpg` | service | Jason Deines | [15293006](https://www.pexels.com/photo/15293006/) | يد بقفاز أزرق تمسح سطح رخام أبيض بقطعة قماش صفراء |
| `floor-marble-cleaning.jpg` | service | Suhasini Kakad | [37969730](https://www.pexels.com/photo/37969730/) | ممر بأرضية رخام لامعة بزخارف وجدران رخامية |
| `ac-cleaning.jpg` | service | NEOSiAM 18+ | [38788452](https://www.pexels.com/photo/wall-mounted-air-conditioner-in-modern-interior-38788452/) | وحدة تكييف سبليت بيضاء مثبتة على جدار داخلي |
| `pool-tank-cleaning.jpg` | service | panumas nikhomkhai | [38127493](https://www.pexels.com/photo/38127493/) | مسبح خارجي بماء صافٍ وسلّم معدني ومقاعد استلقاء |
| `b2b-commercial-team.jpg` | b2b | Tima Miroshnichenko | [6195121](https://www.pexels.com/photo/6195121/) | ثلاثة من أفراد فريق التنظيف بزي موحد يحملون معداتهم |
