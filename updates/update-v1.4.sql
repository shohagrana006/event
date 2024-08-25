ALTER TABLE eventic_amenity CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_amenity_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_app_layout_setting CHANGE logo_name logo_name VARCHAR(50) DEFAULT NULL, CHANGE logo_size logo_size INT DEFAULT NULL, CHANGE logo_mime_type logo_mime_type VARCHAR(50) DEFAULT NULL, CHANGE logo_original_name logo_original_name VARCHAR(1000) DEFAULT NULL, CHANGE logo_dimensions logo_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE favicon_name favicon_name VARCHAR(50) DEFAULT NULL, CHANGE favicon_size favicon_size INT DEFAULT NULL, CHANGE favicon_mime_type favicon_mime_type VARCHAR(50) DEFAULT NULL, CHANGE favicon_original_name favicon_original_name VARCHAR(1000) DEFAULT NULL, CHANGE favicon_dimensions favicon_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE og_image_name og_image_name VARCHAR(50) DEFAULT NULL, CHANGE og_image_size og_image_size INT DEFAULT NULL, CHANGE og_image_mime_type og_image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE og_image_original_name og_image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE og_image_dimensions og_image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)';
ALTER TABLE eventic_audience CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_audience_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_blog_post CHANGE category_id category_id INT DEFAULT NULL, CHANGE readtime readtime INT DEFAULT NULL, CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE views views INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_blog_post_category CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_blog_post_category_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_blog_post_translation CHANGE translatable_id translatable_id INT DEFAULT NULL, CHANGE tags tags VARCHAR(500) DEFAULT NULL;
ALTER TABLE eventic_cart_element CHANGE user_id user_id INT DEFAULT NULL, CHANGE eventticket_id eventticket_id INT DEFAULT NULL, CHANGE quantity quantity INT DEFAULT NULL, CHANGE ticket_fee ticket_fee NUMERIC(10, 2) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_category CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE featuredorder featuredorder INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_category_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_comment CHANGE thread_id thread_id VARCHAR(255) DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL;
ALTER TABLE eventic_country CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_country_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_currency CHANGE symbol symbol VARCHAR(50) DEFAULT NULL;
ALTER TABLE eventic_event CHANGE category_id category_id INT DEFAULT NULL, CHANGE country_id country_id INT DEFAULT NULL, CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE isonhomepageslider_id isonhomepageslider_id INT DEFAULT NULL, CHANGE youtubeurl youtubeurl VARCHAR(255) DEFAULT NULL, CHANGE externallink externallink VARCHAR(255) DEFAULT NULL, CHANGE phonenumber phonenumber VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE twitter twitter VARCHAR(255) DEFAULT NULL, CHANGE instagram instagram VARCHAR(255) DEFAULT NULL, CHANGE facebook facebook VARCHAR(255) DEFAULT NULL, CHANGE googleplus googleplus VARCHAR(255) DEFAULT NULL, CHANGE linkedin linkedin VARCHAR(255) DEFAULT NULL, CHANGE artists artists VARCHAR(500) DEFAULT NULL, CHANGE tags tags VARCHAR(500) DEFAULT NULL, CHANGE year year VARCHAR(5) DEFAULT NULL, CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_event_date CHANGE event_id event_id INT DEFAULT NULL, CHANGE venue_id venue_id INT DEFAULT NULL, CHANGE startdate startdate DATETIME DEFAULT NULL, CHANGE enddate enddate DATETIME DEFAULT NULL;
ALTER TABLE eventic_event_image CHANGE event_id event_id INT DEFAULT NULL, CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE position position INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_event_date_ticket CHANGE eventdate_id eventdate_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE price price NUMERIC(10, 2) DEFAULT NULL, CHANGE promotionalprice promotionalprice NUMERIC(10, 2) DEFAULT NULL, CHANGE quantity quantity INT DEFAULT NULL, CHANGE ticketsperattendee ticketsperattendee INT DEFAULT NULL, CHANGE salesstartdate salesstartdate DATETIME DEFAULT NULL, CHANGE salesenddate salesenddate DATETIME DEFAULT NULL;
ALTER TABLE eventic_event_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_help_center_article CHANGE category_id category_id INT DEFAULT NULL, CHANGE views views INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_help_center_article_translation CHANGE translatable_id translatable_id INT DEFAULT NULL, CHANGE tags tags VARCHAR(150) DEFAULT NULL;
ALTER TABLE eventic_help_center_category CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE icon icon VARCHAR(50) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_help_center_category_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_homepage_hero_setting CHANGE custom_background_name custom_background_name VARCHAR(50) DEFAULT NULL, CHANGE custom_background_size custom_background_size INT DEFAULT NULL, CHANGE custom_background_mime_type custom_background_mime_type VARCHAR(50) DEFAULT NULL, CHANGE custom_background_original_name custom_background_original_name VARCHAR(1000) DEFAULT NULL, CHANGE custom_background_dimensions custom_background_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE show_search_box show_search_box TINYINT(1) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_homepage_hero_setting_translation CHANGE translatable_id translatable_id INT DEFAULT NULL, CHANGE title title VARCHAR(100) DEFAULT NULL, CHANGE paragraph paragraph VARCHAR(500) DEFAULT NULL;
ALTER TABLE eventic_language CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_language_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_menu_element CHANGE menu_id menu_id INT DEFAULT NULL, CHANGE icon icon VARCHAR(50) DEFAULT NULL, CHANGE link link VARCHAR(255) DEFAULT NULL, CHANGE custom_link custom_link VARCHAR(255) DEFAULT NULL;
ALTER TABLE eventic_menu_element_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_menu_translation CHANGE translatable_id translatable_id INT DEFAULT NULL, CHANGE header header VARCHAR(50) DEFAULT NULL;
ALTER TABLE eventic_order CHANGE user_id user_id INT DEFAULT NULL, CHANGE paymentgateway_id paymentgateway_id INT DEFAULT NULL, CHANGE payment_id payment_id INT DEFAULT NULL, CHANGE note note VARCHAR(1000) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_order_element CHANGE order_id order_id INT DEFAULT NULL, CHANGE eventticket_id eventticket_id INT DEFAULT NULL, CHANGE unitprice unitprice NUMERIC(10, 2) DEFAULT NULL, CHANGE quantity quantity INT DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_order_ticket CHANGE orderelement_id orderelement_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_organizer CHANGE user_id user_id INT DEFAULT NULL, CHANGE country_id country_id INT DEFAULT NULL, CHANGE description description VARCHAR(1000) DEFAULT NULL, CHANGE website website VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE facebook facebook VARCHAR(100) DEFAULT NULL, CHANGE twitter twitter VARCHAR(100) DEFAULT NULL, CHANGE instagram instagram VARCHAR(100) DEFAULT NULL, CHANGE googleplus googleplus VARCHAR(100) DEFAULT NULL, CHANGE linkedin linkedin VARCHAR(100) DEFAULT NULL, CHANGE youtubeurl youtubeurl VARCHAR(255) DEFAULT NULL, CHANGE logo_name logo_name VARCHAR(50) DEFAULT NULL, CHANGE logo_size logo_size INT DEFAULT NULL, CHANGE logo_mime_type logo_mime_type VARCHAR(50) DEFAULT NULL, CHANGE logo_original_name logo_original_name VARCHAR(1000) DEFAULT NULL, CHANGE logo_dimensions logo_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE cover_name cover_name VARCHAR(50) DEFAULT NULL, CHANGE cover_size cover_size INT DEFAULT NULL, CHANGE cover_mime_type cover_mime_type VARCHAR(50) DEFAULT NULL, CHANGE cover_original_name cover_original_name VARCHAR(1000) DEFAULT NULL, CHANGE cover_dimensions cover_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE show_event_date_stats_on_scanner_app show_event_date_stats_on_scanner_app TINYINT(1) DEFAULT NULL, CHANGE allow_tap_to_check_in_on_scanner_app allow_tap_to_check_in_on_scanner_app TINYINT(1) DEFAULT NULL;
ALTER TABLE eventic_page CHANGE updated_at updated_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_page_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_payment CHANGE order_id order_id INT DEFAULT NULL, CHANGE country_id country_id INT DEFAULT NULL, CHANGE number number VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE client_email client_email VARCHAR(255) DEFAULT NULL, CHANGE client_id client_id VARCHAR(255) DEFAULT NULL, CHANGE total_amount total_amount INT DEFAULT NULL, CHANGE currency_code currency_code VARCHAR(255) DEFAULT NULL, CHANGE details details JSON NOT NULL COMMENT '(DC2Type:json_array)', CHANGE firstname firstname VARCHAR(20) DEFAULT NULL, CHANGE lastname lastname VARCHAR(20) DEFAULT NULL, CHANGE state state VARCHAR(50) DEFAULT NULL, CHANGE city city VARCHAR(50) DEFAULT NULL, CHANGE postalcode postalcode VARCHAR(50) DEFAULT NULL, CHANGE street street VARCHAR(50) DEFAULT NULL, CHANGE street2 street2 VARCHAR(50) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_payment_gateway CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE config config JSON NOT NULL COMMENT '(DC2Type:json_array)', CHANGE gateway_logo_name gateway_logo_name VARCHAR(255) DEFAULT NULL, CHANGE number number INT DEFAULT NULL;
ALTER TABLE eventic_payment_gateway_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_payment_token CHANGE details details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:object)';
ALTER TABLE eventic_payout_request CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE payment_gateway_id payment_gateway_id INT DEFAULT NULL, CHANGE event_date_id event_date_id INT DEFAULT NULL, CHANGE payment payment JSON DEFAULT NULL COMMENT '(DC2Type:json_array)', CHANGE note note VARCHAR(1000) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_pointofsale CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;
ALTER TABLE eventic_review CHANGE event_id event_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE headline headline VARCHAR(100) DEFAULT NULL, CHANGE details details VARCHAR(500) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_scanner CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL;
ALTER TABLE eventic_settings CHANGE value value VARCHAR(500) DEFAULT NULL;
ALTER TABLE eventic_thread CHANGE last_comment_at last_comment_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_ticket_reservation CHANGE eventticket_id eventticket_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE orderelement_id orderelement_id INT DEFAULT NULL, CHANGE quantity quantity INT DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_user CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE scanner_id scanner_id INT DEFAULT NULL, CHANGE pointofsale_id pointofsale_id INT DEFAULT NULL, CHANGE isorganizeronhomepageslider_id isorganizeronhomepageslider_id INT DEFAULT NULL, CHANGE country_id country_id INT DEFAULT NULL, CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL, CHANGE password_requested_at password_requested_at DATETIME DEFAULT NULL, CHANGE gender gender VARCHAR(10) DEFAULT NULL, CHANGE firstname firstname VARCHAR(20) DEFAULT NULL, CHANGE lastname lastname VARCHAR(20) DEFAULT NULL, CHANGE street street VARCHAR(50) DEFAULT NULL, CHANGE street2 street2 VARCHAR(50) DEFAULT NULL, CHANGE city city VARCHAR(50) DEFAULT NULL, CHANGE state state VARCHAR(50) DEFAULT NULL, CHANGE postalcode postalcode VARCHAR(15) DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE birthdate birthdate DATE DEFAULT NULL, CHANGE avatar_name avatar_name VARCHAR(50) DEFAULT NULL, CHANGE avatar_size avatar_size INT DEFAULT NULL, CHANGE avatar_mime_type avatar_mime_type VARCHAR(50) DEFAULT NULL, CHANGE avatar_original_name avatar_original_name VARCHAR(1000) DEFAULT NULL, CHANGE avatar_dimensions avatar_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE facebook_id facebook_id VARCHAR(255) DEFAULT NULL, CHANGE facebook_access_token facebook_access_token VARCHAR(255) DEFAULT NULL, CHANGE google_id google_id VARCHAR(255) DEFAULT NULL, CHANGE google_access_token google_access_token VARCHAR(255) DEFAULT NULL, CHANGE api_key api_key VARCHAR(255) DEFAULT NULL, CHANGE facebook_profile_picture facebook_profile_picture VARCHAR(1000) DEFAULT NULL;
ALTER TABLE eventic_venue CHANGE organizer_id organizer_id INT DEFAULT NULL, CHANGE type_id type_id INT DEFAULT NULL, CHANGE country_id country_id INT DEFAULT NULL, CHANGE seatedguests seatedguests INT DEFAULT NULL, CHANGE standingguests standingguests INT DEFAULT NULL, CHANGE neighborhoods neighborhoods VARCHAR(100) DEFAULT NULL, CHANGE foodbeverage foodbeverage VARCHAR(500) DEFAULT NULL, CHANGE pricing pricing VARCHAR(500) DEFAULT NULL, CHANGE availibility availibility VARCHAR(500) DEFAULT NULL, CHANGE street2 street2 VARCHAR(50) DEFAULT NULL, CHANGE lat lat VARCHAR(255) DEFAULT NULL, CHANGE lng lng VARCHAR(255) DEFAULT NULL, CHANGE contactemail contactemail VARCHAR(50) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_venue_image CHANGE venue_id venue_id INT DEFAULT NULL, CHANGE image_name image_name VARCHAR(50) DEFAULT NULL, CHANGE image_size image_size INT DEFAULT NULL, CHANGE image_mime_type image_mime_type VARCHAR(50) DEFAULT NULL, CHANGE image_original_name image_original_name VARCHAR(1000) DEFAULT NULL, CHANGE image_dimensions image_dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', CHANGE position position INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_venue_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_venue_type CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL;
ALTER TABLE eventic_venue_type_translation CHANGE translatable_id translatable_id INT DEFAULT NULL;
ALTER TABLE eventic_vote CHANGE comment_id comment_id INT DEFAULT NULL, CHANGE voter_id voter_id INT DEFAULT NULL;



INSERT INTO `eventic_menu_translation` (`translatable_id`, `name`, `header`, `slug`, `locale`) VALUES
(2, 'Menu della prima sezione piè di pagina', 'link utili', 'menu-della-prima-sezione-pie-di-pagina', 'it'),
(2, 'Menu da seção do primeiro rodapé', 'Links úteis', 'menu-da-secao-do-primeiro-rodape-1', 'br'),
(3, 'Menu della seconda sezione piè di pagina', 'Il mio conto', 'menu-della-seconda-sezione-pie-di-pagina', 'it'),
(3, 'Menu da seção do segundo rodapé', 'Minha conta', 'menu-da-secao-do-segundo-rodape-1', 'br'),
(4, 'Menu della sezione del terzo piè di pagina', 'Categorie di eventi', 'menu-della-sezione-del-terzo-pie-di-pagina', 'it'),
(4, 'Menu da seção do terceiro rodapé', 'Categorias de eventos', 'menu-da-secao-do-terceiro-rodape-1', 'br');

INSERT INTO `eventic_menu_element_translation` (`translatable_id`, `label`, `slug`, `locale`) VALUES
(1, 'Casa', 'casa-1', 'it'),
(1, 'Início', 'inicio', 'br'),
(2, 'Sfoglia eventi', 'sfoglia-eventi', 'it'),
(2, 'Procurar eventos', 'procurar-eventos', 'br'),
(3, 'Esplorare', 'esplorare', 'it'),
(3, 'Explorar', 'explorar-2', 'br'),
(4, 'Luoghi', 'luoghi', 'it'),
(4, 'Locais', 'locais-2', 'br'),
(5, 'Come funziona?', 'come-funziona', 'it'),
(5, 'Como funciona?', 'como-funciona-2', 'br'),
(6, 'Blog', 'blog-10', 'it'),
(6, 'Blog', 'blog-11', 'br'),
(7, 'I miei biglietti', 'i-miei-biglietti', 'it'),
(7, 'Meus ingressos', 'meus-ingressos-2', 'br'),
(8, 'Aggiungi il mio evento', 'aggiungi-il-mio-evento', 'it'),
(8, 'Adicionar meu evento', 'adicionar-meu-evento-1', 'br'),
(10, 'Centro assistenza', 'centro-assistenza', 'it'),
(10, 'Central de ajuda', 'central-de-ajuda', 'br'),
(11, 'Blog', 'blog-12', 'it'),
(11, 'Blog', 'blog-13', 'br'),
(12, 'Luoghi', 'luoghi-1', 'it'),
(12, 'Locais', 'locais-3', 'br'),
(13, 'Mandaci un email', 'mandaci-un-email', 'it'),
(13, 'Envie-nos um e-mail', 'envie-nos-um-e-mail-1', 'br'),
(14, 'Creare un account', 'creare-un-account', 'it'),
(14, 'Criar uma conta', 'criar-uma-conta', 'br'),
(15, 'Vendi biglietti on-line', 'vendi-biglietti-on-line', 'it'),
(15, 'Venda de ingressos on-line', 'venda-de-ingressos-on-line', 'br'),
(16, 'I miei biglietti', 'i-miei-biglietti-1', 'it'),
(16, 'Meus ingressos', 'meus-ingressos-3', 'br'),
(17, 'Hai dimenticato la password ?', 'hai-dimenticato-la-password', 'it'),
(17, 'Esqueceu sua senha?', 'esqueceu-sua-senha-1', 'br'),
(18, 'Prezzi e commissioni', 'prezzi-e-commissioni', 'it'),
(18, 'Preços e taxas', 'precos-e-taxas-1', 'br'),
(20, 'Tutte le categorie', 'tutte-le-categorie', 'it'),
(20, 'Todas as categorias', 'todas-as-categorias', 'br');

INSERT INTO `eventic_homepage_hero_setting_translation` (`translatable_id`, `title`, `paragraph`, `locale`) VALUES
(1, 'Eventic', 'Gestione eventi online e vendita biglietti', 'it'),
(1, 'Eventic', 'Gerenciamento de eventos on-line e venda de ingressos', 'br');

INSERT INTO `eventic_category_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(1, 'Cinema', 'cinema-3', 'it'),
(1, 'Cinema', 'cinema-4', 'br'),
(2, 'Teatro', 'teatro-2', 'it'),
(2, 'Teatro', 'teatro-3', 'br'),
(3, 'Concerto/Musica', 'concertomusica', 'it'),
(3, 'Concerto / Música', 'concerto-musica-1', 'br'),
(4, 'Viaggio / Campeggio', 'viaggio-campeggio', 'it'),
(4, 'Viagem / Acampamento', 'viagem-acampamento-1', 'br'),
(5, 'Workshop / Formazione', 'workshop-formazione', 'it'),
(5, 'Workshop / Treinamento', 'workshop-treinamento', 'br'),
(6, 'Conferenza', 'conferenza', 'it'),
(6, 'Conferência', 'conferencia-2', 'br'),
(7, 'Festival / Spettacolo', 'festival-spettacolo', 'it'),
(7, 'Festival / Espetáculo', 'festival-espetaculo-1', 'br'),
(8, 'Gioco / Competizione', 'gioco-competizione', 'it'),
(8, 'Jogo / Competição', 'jogo-competicao-1', 'br'),
(9, 'Esposizione', 'esposizione', 'it'),
(9, 'Exposição', 'exposicao-1', 'br'),
(10, 'Sport / forma fisica', 'sport-forma-fisica', 'it'),
(10, 'Esporte / Fitness', 'esporte-fitness-1', 'br'),
(11, 'Museo / Monumento', 'museo-monumento-1', 'it'),
(11, 'Museu / Monumento', 'museu-monumento-1', 'br'),
(12, 'Ristorante / Gastronomia', 'ristorante-gastronomia', 'it'),
(12, 'Restaurante / Gastronomia', 'restaurante-gastronomia-2', 'br'),
(13, 'Parco ricreativo / Attrazione', 'parco-ricreativo-attrazione', 'it'),
(13, 'Parque de recreação / Atração', 'parque-de-recreacao-atracao-1', 'br'),
(14, 'Parcheggio / Servizi', 'parcheggio-servizi', 'it'),
(14, 'Estacionamento / Serviços', 'estacionamento-servicos-1', 'br');

INSERT INTO `eventic_audience_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(2, 'Bambini', 'bambini', 'it'),
(2, 'Crianças', 'criancas-1', 'br'),
(1, 'Adulti', 'adulti', 'it'),
(1, 'Adultos', 'adultos-2', 'br'),
(5, 'Gioventù', 'gioventu', 'it'),
(5, 'Jovens', 'jovens', 'br'),
(4, 'Gruppo', 'gruppo', 'it'),
(4, 'Grupo', 'grupo-2', 'br'),
(3, 'Famiglia', 'famiglia', 'it'),
(3, 'Família', 'familia-2', 'br');

INSERT INTO `eventic_venue_type_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(1, 'Sala del banchetto', 'sala-del-banchetto', 'it'),
(1, 'Salão de banquetes', 'salao-de-banquetes', 'br'),
(2, 'Sbarra', 'sbarra', 'it'),
(2, 'Bar', 'bar-3', 'br'),
(3, 'Barca', 'barca', 'it'),
(3, 'Barco', 'barco', 'br'),
(4, 'Birrificio', 'birrificio', 'it'),
(4, 'Cervejaria', 'cervejaria', 'br'),
(5, 'Café', 'cafe-3', 'br'),
(6, 'Spazio di coworking', 'spazio-di-coworking', 'it'),
(6, 'Espaço de trabalho conjunto', 'espaco-de-trabalho-conjunto', 'br'),
(7, 'Centro conferenze', 'centro-conferenze', 'it'),
(7, 'Centro de conferências', 'centro-de-conferencias-1', 'br'),
(8, 'Country Club', 'country-club-2', 'it'),
(8, 'Clube de Campo', 'clube-de-campo', 'br'),
(9, 'Spazio eventi', 'spazio-eventi', 'it'),
(9, 'Espaço para eventos', 'espaco-para-eventos', 'br'),
(10, 'Galleria', 'galleria', 'it'),
(10, 'Galeria', 'galeria-1', 'br'),
(11, 'Palestra', 'palestra', 'it'),
(11, 'Ginásio', 'ginasio', 'br'),
(12, 'Hotel', 'hotel-3', 'it'),
(12, 'Hotel', 'hotel-4', 'br'),
(13, 'Soppalco', 'soppalco', 'it'),
(13, 'Loft', 'loft-1', 'br'),
(14, 'Spazio per riunioni', 'spazio-per-riunioni', 'it'),
(14, 'Espaço para reuniões', 'espaco-para-reunioes', 'br'),
(15, 'Museo', 'museo-1', 'it'),
(15, 'Museu', 'museu', 'br'),
(16, 'Ristorante', 'ristorante', 'it'),
(16, 'Restaurante', 'restaurante-1', 'br'),
(17, 'Stadio', 'stadio', 'it'),
(17, 'Estádio', 'estadio-1', 'br'),
(18, 'Teatro', 'teatro-1', 'it'),
(18, 'Teatro', 'teatro-2', 'br'),
(19, 'Altro', 'altro', 'it'),
(19, 'Outros', 'outros', 'br');

INSERT INTO `eventic_amenity_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(17, 'Spazio teatrale', 'spazio-teatrale', 'it'),
(17, 'spaço do teatro', 'spaco-do-teatro', 'br'),
(16, 'Sul tetto', 'sul-tetto', 'it'),
(16, 'Telhado', 'telhado-1', 'br'),
(15, 'Sala multimediale', 'sala-multimediale', 'it'),
(15, 'Sala de mídia', 'sala-de-midia-1', 'br'),
(14, 'Parcheggio', 'parcheggio', 'it'),
(14, 'Estacionamento', 'estacionamento-1', 'br'),
(13, 'Sale per riunioni', 'sale-per-riunioni', 'it'),
(13, 'Salas de descanso', 'salas-de-descanso-2', 'br'),
(12, 'Attrezzatura audio / video', 'attrezzatura-audio-video', 'it'),
(12, 'Equipamento A/V', 'equipamento-av', 'br'),
(11, 'WiFi', 'wifi-4', 'it'),
(11, 'WiFi', 'wifi-5', 'br'),
(10, 'Cucciolo amichevole', 'cucciolo-amichevole', 'it'),
(10, 'Aceita animais de estimação', 'aceita-animais-de-estimacao', 'br'),
(9, 'Spazio all aperto', 'spazio-allaperto', 'it'),
(9, 'Espaço externo', 'espaco-externo', 'br'),
(8, 'Accessibile ai disabili', 'accessibile-ai-disabili', 'it'),
(8, 'Acessível para deficientes', 'acessivel-para-deficientes-1', 'br'),
(7, 'Centro affari', 'centro-affari', 'it'),
(7, 'Centro de negócios', 'centro-de-negocios-2', 'br'),
(6, 'Fronte mare', 'fronte-mare', 'it'),
(6, 'Em frente à praia', 'em-frente-a-praia', 'br'),
(5, 'Terme', 'terme', 'it'),
(5, 'Spa', 'spa-4', 'br');

INSERT INTO `eventic_blog_post_category_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(17, 'Pianificazione', 'pianificazione', 'it'),
(17, 'Planejamento', 'planejamento-1', 'br'),
(16, 'Suggerimenti', 'suggerimenti', 'it'),
(16, 'Dicas', 'dicas', 'br'),
(15, 'Altro', 'altro', 'it'),
(15, 'Outros', 'outros', 'br'),
(14, 'Sponsorizzazione', 'sponsorizzazione', 'it'),
(14, 'Patrocínio', 'patrocinio-1', 'br'),
(13, 'Mezzi sociali', 'mezzi-sociali', 'it'),
(13, 'Mídia social', 'midia-social-1', 'br'),
(12, 'Marketing', 'marketing-4', 'it'),
(12, 'Marketing', 'marketing-5', 'br'),
(11, 'Prezzi', 'prezzi', 'it'),
(11, 'Preços', 'precos-1', 'br'),
(10, 'Notizia', 'notizia', 'it'),
(10, 'Notícias', 'noticias-1', 'br'),
(9, 'Caratteristica', 'caratteristica', 'it'),
(9, 'Recurso', 'recurso-1', 'br'),
(8, 'Contenuto', 'contenuto', 'it'),
(8, 'Conteúdo', 'conteudo-1', 'br'),
(7, 'Comunità', 'comunita', 'it'),
(7, 'Comunidade', 'comunidade-1', 'br'),
(6, 'Collaborazione', 'collaborazione', 'it'),
(6, 'Colaboração', 'colaboracao-1', 'br'),
(5, 'Ristorazione', 'ristorazione', 'it'),
(5, 'Serviço de bufê', 'servico-de-bufe', 'br'),
(4, 'Budgeting', 'budgeting-1', 'it'),
(4, 'Orçamento', 'orcamento', 'br'),
(3, 'Marchio', 'marchio', 'it'),
(3, 'Marca', 'marca-1', 'br'),
(2, 'Partecipanti', 'partecipanti', 'it'),
(2, 'Participantes', 'participantes-1', 'br');

INSERT INTO `eventic_help_center_category_translation` (`translatable_id`, `name`, `slug`, `locale`) VALUES
(2, 'Organizzatore', 'organizzatore', 'it'),
(2, 'Organizador', 'organizador-2', 'br'),
(1, 'Partecipante', 'partecipante', 'it'),
(1, 'Participante', 'participante-1', 'br');

INSERT INTO `eventic_settings` (`key`, `value`) VALUES
('website_description_it', 'Gestione eventi e vendita biglietti'),
('website_description_br', 'Gerenciamento de eventos e venda de ingressos');

INSERT INTO `eventic_settings` (`key`, `value`) VALUES
('website_keywords_it', 'organizzare il mio evento, biglietti online, acquistare i biglietti'),
('website_keywords_br', 'organizar meu evento, ingressos on-line, comprar ingressos');