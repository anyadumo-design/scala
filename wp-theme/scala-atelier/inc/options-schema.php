<?php
/**
 * Опис усіх налаштувань теми, розкладених по вкладках.
 *
 * Що редагується тут — тексти, фото й підписи, які існують в одному
 * екземплярі. Списки, що ростуть (проєкти, види штор, тканини, каталог,
 * відгуки, питання, Instagram), винесені в окремі типи записів.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Схема налаштувань: вкладка => поля.
 *
 * @return array
 */
function scala_options_schema(): array {
	return array(

		/* ------------------------------------------------ Загальне ---- */
		'general' => array(
			'label'  => __( 'Загальне', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'phone',
					'type'    => 'text',
					'label'   => __( 'Телефон', 'scala' ),
					'default' => '(097) 123-33-30',
					'help'    => __( 'Як показувати на сайті. Посилання tel: збирається автоматично.', 'scala' ),
				),
				array(
					'key'     => 'email',
					'type'    => 'email',
					'label'   => __( 'Пошта', 'scala' ),
					'default' => 'info@scalahome.com.ua',
				),
				array(
					'key'     => 'instagram',
					'type'    => 'url',
					'label'   => __( 'Instagram', 'scala' ),
					'default' => 'https://www.instagram.com/scala_home_',
				),
				array(
					'key'     => 'instagram_handle',
					'type'    => 'text',
					'label'   => __( 'Нік в Instagram', 'scala' ),
					'default' => '@scala_home_',
				),
				array(
					'key'     => 'telegram',
					'type'    => 'url',
					'label'   => __( 'Telegram', 'scala' ),
					'default' => 'https://t.me/ScalaHome',
				),
				array(
					'key'     => 'whatsapp',
					'type'    => 'url',
					'label'   => __( 'WhatsApp', 'scala' ),
					'default' => 'https://wa.me/qr/PTTFG72MUVOOD1',
				),
				array(
					'key'     => 'city',
					'type'    => 'text',
					'label'   => __( 'Місто', 'scala' ),
					'default' => 'Київ',
					'help'    => __( 'Використовується у структурованих даних (areaServed).', 'scala' ),
				),
				array(
					'key'     => 'address',
					'type'    => 'text',
					'label'   => __( 'Адреса шоуруму', 'scala' ),
					'help'    => __( 'Поки порожньо — у структурованих даних адреса не виводиться. Заповніть, щоб звʼязати сайт із карткою Google Business.', 'scala' ),
				),
				array(
					'key'     => 'hours',
					'type'    => 'textarea',
					'label'   => __( 'Графік роботи', 'scala' ),
					'rows'    => 2,
					'default' => 'Адресу шоуруму та години роботи додамо після підтвердження.',
				),
				array(
					'key'     => 'map_embed',
					'type'    => 'textarea',
					'label'   => __( 'Посилання на карту (Google Maps embed)', 'scala' ),
					'rows'    => 2,
					'help'    => __( 'Лише URL із параметра src у коді вбудовування. Порожньо — блок карти не показується.', 'scala' ),
				),
				array(
					'key'     => 'footer_about',
					'type'    => 'textarea',
					'label'   => __( 'Опис у футері', 'scala' ),
					'rows'    => 2,
					'default' => 'Мистецтво тканини. Елітні штори та інтерʼєрний текстиль на замовлення.',
				),
				array(
					'key'     => 'footer_tagline',
					'type'    => 'text',
					'label'   => __( 'Рядок біля копірайту', 'scala' ),
					'default' => 'Штори на замовлення в Києві',
				),
			),
		),

		/* ---------------------------------------------------- Hero ---- */
		'hero' => array(
			'label'  => __( 'Головний екран', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'slogan',
					'type'    => 'textarea',
					'label'   => __( 'Слоган (великий текст)', 'scala' ),
					'rows'    => 2,
					'default' => 'Штори, які <br />змінюють кімнату',
					'help'    => __( 'Це НЕ заголовок сторінки — просто великий напис. Можна вживати <br> для переносу.', 'scala' ),
				),
				array(
					'key'     => 'h1',
					'type'    => 'text',
					'label'   => __( 'Заголовок H1', 'scala' ),
					'default' => 'Пошиття штор на замовлення в Києві',
					'help'    => __( 'Головний заголовок для пошуку. Має містити запит і місто — не замінюйте на слоган.', 'scala' ),
				),
				array(
					'key'     => 'sub',
					'type'    => 'textarea',
					'label'   => __( 'Підзаголовок', 'scala' ),
					'rows'    => 2,
					'default' => 'Дизайнер приїде зі зразками в межах Києва, підбере тканину у вашому освітленні та супроводить проєкт до монтажу.',
				),
				array(
					'key'     => 'btn_primary',
					'type'    => 'text',
					'label'   => __( 'Кнопка головна', 'scala' ),
					'default' => 'Запросити дизайнера зі зразками',
				),
				array(
					'key'     => 'btn_secondary',
					'type'    => 'text',
					'label'   => __( 'Кнопка друга', 'scala' ),
					'default' => 'Подивитися роботи',
				),
				array(
					'key'     => 'hint',
					'type'    => 'text',
					'label'   => __( 'Підказка скролу', 'scala' ),
					'default' => 'Прокрутіть, щоб відкрити ↓',
				),
				array(
					'key'   => 'room_image',
					'type'  => 'image',
					'label' => __( 'Фото кімнати (за шторою)', 'scala' ),
					'help'  => __( 'Горизонтальне, 16:9. Видно, коли штора розсувається.', 'scala' ),
				),
				array(
					'key'   => 'curtain_image',
					'type'  => 'image',
					'label' => __( 'Фото штори (сама завіса)', 'scala' ),
					'help'  => __( 'Закрите полотно зі стиком по центру: фото ділиться навпіл і роз’їжджається.', 'scala' ),
				),
				array(
					'key'     => 'reveal_text',
					'type'    => 'textarea',
					'label'   => __( 'Текст, що зʼявляється під шторою', 'scala' ),
					'rows'    => 2,
					'default' => 'Більше світла. Більше приватності. <br /><span class="muted">Інша атмосфера кімнати.</span>',
				),
			),
		),

		/* ------------------------------------------------ Секції ------ */
		'sections' => array(
			'label'  => __( 'Заголовки секцій', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'benefits_eyebrow',
					'type'    => 'text',
					'label'   => __( '01 — надпис', 'scala' ),
					'default' => 'Що змінюють правильні штори',
				),
				array(
					'key'     => 'scenarios_eyebrow',
					'type'    => 'text',
					'label'   => __( '02 — надпис', 'scala' ),
					'default' => 'Сценарії оформлення',
				),
				array(
					'key'     => 'scenarios_title',
					'type'    => 'text',
					'label'   => __( '02 — заголовок', 'scala' ),
					'default' => 'Один інтерʼєр — три сценарії світла',
				),
				array(
					'key'     => 'types_eyebrow',
					'type'    => 'text',
					'label'   => __( '03 — надпис', 'scala' ),
					'default' => 'Види штор',
				),
				array(
					'key'     => 'types_title',
					'type'    => 'text',
					'label'   => __( '03 — заголовок', 'scala' ),
					'default' => 'Що ми шиємо',
				),
				array(
					'key'     => 'projects_eyebrow',
					'type'    => 'text',
					'label'   => __( '04 — надпис', 'scala' ),
					'default' => 'Реалізовані проєкти',
				),
				array(
					'key'     => 'process_eyebrow',
					'type'    => 'text',
					'label'   => __( '05 — надпис', 'scala' ),
					'default' => 'Як працюємо',
				),
				array(
					'key'     => 'fabrics_eyebrow',
					'type'    => 'text',
					'label'   => __( '06 — надпис', 'scala' ),
					'default' => 'Тканини Scala',
				),
				array(
					'key'     => 'fabrics_title',
					'type'    => 'text',
					'label'   => __( '06 — заголовок', 'scala' ),
					'default' => 'Доторкнутись поки не можна. Роздивитись — можна.',
				),
				array(
					'key'     => 'fabrics_lead',
					'type'    => 'textarea',
					'label'   => __( '06 — опис', 'scala' ),
					'rows'    => 2,
					'default' => 'Дизайнер привезе відрізи додому: при вашому світлі тканина виглядає інакше, ніж у каталозі.',
				),
				array(
					'key'     => 'faq_eyebrow',
					'type'    => 'text',
					'label'   => __( '07 — надпис', 'scala' ),
					'default' => 'Часті питання',
				),
				array(
					'key'     => 'faq_title',
					'type'    => 'text',
					'label'   => __( '07 — заголовок', 'scala' ),
					'default' => 'Що варто знати до заявки',
				),
				array(
					'key'     => 'ig_eyebrow',
					'type'    => 'text',
					'label'   => __( '08 — надпис', 'scala' ),
					'default' => 'Instagram',
				),
				array(
					'key'     => 'ig_lead',
					'type'    => 'textarea',
					'label'   => __( '08 — опис', 'scala' ),
					'rows'    => 2,
					'default' => 'Процес пошиття, монтажі, нові колекції тканин і кадри з реальних квартир.',
				),
				array(
					'key'     => 'cta1_title',
					'type'    => 'text',
					'label'   => __( 'CTA-смуга 1 — заголовок', 'scala' ),
					'default' => 'Не впевнені, який сценарій ваш?',
				),
				array(
					'key'     => 'cta1_text',
					'type'    => 'textarea',
					'label'   => __( 'CTA-смуга 1 — текст', 'scala' ),
					'rows'    => 2,
					'default' => 'Дизайнер приїде зі зразками й покаже обидва варіанти у вашому освітленні.',
				),
				array(
					'key'     => 'cta1_btn',
					'type'    => 'text',
					'label'   => __( 'CTA-смуга 1 — кнопка', 'scala' ),
					'default' => 'Запросити дизайнера',
				),
				array(
					'key'     => 'cta2_title',
					'type'    => 'text',
					'label'   => __( 'CTA-смуга 2 — заголовок', 'scala' ),
					'default' => 'Хочете так само у своїй кімнаті?',
				),
				array(
					'key'     => 'cta2_text',
					'type'    => 'textarea',
					'label'   => __( 'CTA-смуга 2 — текст', 'scala' ),
					'rows'    => 2,
					'default' => 'Прорахунок за вашими вікнами — після безкоштовного заміру, без зобовʼязань.',
				),
				array(
					'key'     => 'cta2_btn',
					'type'    => 'text',
					'label'   => __( 'CTA-смуга 2 — кнопка', 'scala' ),
					'default' => 'Отримати прорахунок',
				),
			),
		),

		/* ------------------------------------------------- Списки ----- */
		'lists' => array(
			'label'  => __( 'Списки на головній', 'scala' ),
			'fields' => array(
				array(
					'key'       => 'benefits',
					'type'      => 'repeater',
					'label'     => __( '01 — Переваги', 'scala' ),
					'row_label' => __( 'Перевага', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'title',
							'type'  => 'text',
							'label' => __( 'Назва', 'scala' ),
						),
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'rows'  => 2,
							'label' => __( 'Опис', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'scenarios',
					'type'      => 'repeater',
					'label'     => __( '02 — Сценарії світла', 'scala' ),
					'row_label' => __( 'Сценарій', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'title',
							'type'  => 'text',
							'label' => __( 'Назва (на кнопці)', 'scala' ),
						),
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'rows'  => 2,
							'label' => __( 'Опис', 'scala' ),
						),
						array(
							'key'   => 'image',
							'type'  => 'image',
							'label' => __( 'Фото', 'scala' ),
						),
						array(
							'key'   => 'dim',
							'type'  => 'checkbox',
							'label' => __( 'Затемнювати кадр (для блекауту)', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'process',
					'type'      => 'repeater',
					'label'     => __( '05 — Як працюємо', 'scala' ),
					'row_label' => __( 'Крок', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'title',
							'type'  => 'text',
							'label' => __( 'Назва кроку', 'scala' ),
						),
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'rows'  => 2,
							'label' => __( 'Опис', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'includes',
					'type'      => 'repeater',
					'label'     => __( 'Що входить у послугу', 'scala' ),
					'row_label' => __( 'Пункт', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'text',
							'type'  => 'text',
							'label' => __( 'Текст', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'works',
					'type'      => 'repeater',
					'label'     => __( 'Фото робіт (біля відгуків)', 'scala' ),
					'row_label' => __( 'Фото', 'scala' ),
					'help'      => __( 'Квадрат 1:1, від 800×800. Кадр обрізається по центру, тож головне тримайте по центру. Три фото в ряд.', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'image',
							'type'  => 'image',
							'label' => __( 'Зображення', 'scala' ),
						),
						array(
							'key'   => 'alt',
							'type'  => 'text',
							'label' => __( 'Опис для пошуку (alt)', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'screenshots',
					'type'      => 'repeater',
					'label'     => __( 'Скріншоти з переписок', 'scala' ),
					'row_label' => __( 'Скрін', 'scala' ),
					'help'      => __( 'Вертикальний кадр 3:4, від 900×1200. Скрін із телефона майже завжди вужчий і довший, тож обріжте його до трьох-чотирьох повідомлень, які варто прочитати: решту все одно зріже. Перед завантаженням заретушуйте прізвища, номери й аватарки — це особисті дані клієнта.', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'image',
							'type'  => 'image',
							'label' => __( 'Зображення', 'scala' ),
						),
					),
				),
			),
		),

		/* --------------------------------------------------- Форма ---- */
		'form' => array(
			'label'  => __( 'Форма заявки', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'title',
					'type'    => 'text',
					'label'   => __( 'Заголовок', 'scala' ),
					'default' => 'Отримати індивідуальний прорахунок',
				),
				array(
					'key'     => 'text',
					'type'    => 'textarea',
					'label'   => __( 'Опис', 'scala' ),
					'rows'    => 3,
					'default' => 'Вартість залежить від розміру вікна, типу конструкції, тканини, карниза та складності монтажу. Дизайнер приїде зі зразками й розрахує кошторис для ваших вікон.',
				),
				array(
					'key'     => 'note',
					'type'    => 'textarea',
					'label'   => __( 'Примітка під описом', 'scala' ),
					'rows'    => 2,
					'default' => 'Менеджер звʼяжеться з вами в робочий час, щоб узгодити дату виїзду.',
				),
				array(
					'key'     => 'button',
					'type'    => 'text',
					'label'   => __( 'Напис на кнопці', 'scala' ),
					'default' => 'Запросити дизайнера',
				),
				array(
					'key'     => 'consent',
					'type'    => 'textarea',
					'label'   => __( 'Текст про згоду', 'scala' ),
					'rows'    => 2,
					'default' => 'Натискаючи кнопку, ви погоджуєтесь на використання ваших даних для звʼязку.',
				),
				array(
					'key'       => 'needs',
					'type'      => 'repeater',
					'label'     => __( 'Варіанти у списку «Що потрібно оформити»', 'scala' ),
					'row_label' => __( 'Варіант', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'text',
							'type'  => 'text',
							'label' => __( 'Назва', 'scala' ),
						),
					),
				),
				array(
					'key'   => 'photo',
					'type'  => 'image',
					'label' => __( 'Фото біля форми', 'scala' ),
				),
				array(
					'key'     => 'sent_title',
					'type'    => 'text',
					'label'   => __( 'Заголовок після відправки', 'scala' ),
					'default' => 'Заявку прийнято',
				),
				array(
					'key'     => 'notify_email',
					'type'    => 'email',
					'label'   => __( 'Куди надсилати заявки', 'scala' ),
					'help'    => __( 'Порожньо — на адресу адміністратора сайту. Кілька адрес — через кому.', 'scala' ),
				),
			),
		),

		/* ------------------------------------------------ Сторінки ---- */
		'pages' => array(
			'label'  => __( 'Внутрішні сторінки', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'about_lead',
					'type'    => 'textarea',
					'label'   => __( 'Про бренд — лід', 'scala' ),
					'rows'    => 3,
					'default' => 'Scala створює інтерʼєрну розкіш: елітні штори й текстиль, де досконалість — у кожній нитці.',
				),
				array(
					'key'   => 'about_image',
					'type'  => 'image',
					'label' => __( 'Про бренд — велике фото', 'scala' ),
				),
				array(
					'key'     => 'about_h2',
					'type'    => 'text',
					'label'   => __( 'Про бренд — підзаголовок', 'scala' ),
					'default' => 'Ми працюємо з текстилем як з архітектурою вікна',
				),
				array(
					'key'   => 'about_body',
					'type'  => 'textarea',
					'label' => __( 'Про бренд — текст', 'scala' ),
					'rows'  => 6,
				),
				array(
					'key'       => 'about_services',
					'type'      => 'repeater',
					'label'     => __( 'Про бренд — послуги', 'scala' ),
					'row_label' => __( 'Послуга', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'title',
							'type'  => 'text',
							'label' => __( 'Назва', 'scala' ),
						),
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'rows'  => 2,
							'label' => __( 'Опис', 'scala' ),
						),
					),
				),
				array(
					'key'       => 'about_gallery',
					'type'      => 'repeater',
					'label'     => __( 'Про бренд — галерея', 'scala' ),
					'row_label' => __( 'Кадр', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'image',
							'type'  => 'image',
							'label' => __( 'Зображення', 'scala' ),
						),
						array(
							'key'   => 'alt',
							'type'  => 'text',
							'label' => __( 'Опис для пошуку (alt)', 'scala' ),
						),
					),
				),
				array(
					'key'     => 'catalog_lead',
					'type'    => 'textarea',
					'label'   => __( 'Каталог — лід', 'scala' ),
					'rows'    => 3,
					'default' => 'Каталог — для орієнтації у фактурах і конструкціях. Остаточний вибір робиться на замірі.',
				),
				array(
					'key'     => 'home_prose_eyebrow',
					'type'    => 'text',
					'label'   => __( 'Головна — надпис над текстовим блоком', 'scala' ),
					'default' => 'Про послугу',
				),
				array(
					'key'     => 'home_prose_title',
					'type'    => 'text',
					'label'   => __( 'Головна — заголовок текстового блоку', 'scala' ),
					'default' => 'Штори на замовлення в Києві — від заміру до монтажу',
					'help'    => __( 'Сам текст редагується у вмісті сторінки, призначеної головною.', 'scala' ),
				),
				array(
					'key'     => 'catalog_prose_eyebrow',
					'type'    => 'text',
					'label'   => __( 'Каталог — надпис над текстовим блоком', 'scala' ),
					'default' => 'Путівник',
				),
				array(
					'key'     => 'catalog_prose_title',
					'type'    => 'text',
					'label'   => __( 'Каталог — заголовок текстового блоку', 'scala' ),
					'default' => 'Як обрати штори: з чого починати',
				),
				array(
					'key'     => 'about_prose_eyebrow',
					'type'    => 'text',
					'label'   => __( 'Про бренд — надпис над текстовим блоком', 'scala' ),
					'default' => 'Як ми працюємо',
				),
				array(
					'key'     => 'about_prose_title',
					'type'    => 'text',
					'label'   => __( 'Про бренд — заголовок текстового блоку', 'scala' ),
					'default' => 'Чотири етапи, за якими вікно стає готовим',
				),
				array(
					'key'     => 'contacts_prose_eyebrow',
					'type'    => 'text',
					'label'   => __( 'Контакти — надпис над текстовим блоком', 'scala' ),
					'default' => 'Виїзд дизайнера',
				),
				array(
					'key'     => 'contacts_prose_title',
					'type'    => 'text',
					'label'   => __( 'Контакти — заголовок текстового блоку', 'scala' ),
					'default' => 'Як проходить безкоштовний замір',
				),
				array(
					'key'     => 'contacts_lead',
					'type'    => 'textarea',
					'label'   => __( 'Контакти — лід', 'scala' ),
					'rows'    => 2,
					'default' => 'Відповідаємо у месенджерах і телефоном. Виїзд дизайнера зі зразками узгоджуємо на зручний вам час.',
				),
			),
		),
	);
}

/**
 * Плаский список полів однієї вкладки.
 *
 * @param string $tab Ключ вкладки.
 * @return array
 */
function scala_tab_fields( string $tab ): array {
	$schema = scala_options_schema();

	return (array) ( $schema[ $tab ]['fields'] ?? array() );
}
