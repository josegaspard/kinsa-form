

// Register Custom Post Type
function lr_kinsa_register_post_type() {
    register_post_type('lr_kinsa_form', array(
        'labels' => array(
            'name'               => 'Formularios',
            'singular_name'      => 'Formulario',
            'menu_name'          => 'Formularios',
            'add_new'            => 'Nuevo Formulario',
            'add_new_item'       => 'Añadir Nuevo Formulario',
            'edit_item'          => 'Editar Formulario',
            'view_item'          => 'Ver Formulario',
            'all_items'          => 'Todos los Formularios',
            'search_items'       => 'Buscar Formularios'
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-feedback',
        'menu_position'       => 20,
        'supports'            => array('title'),
        'capability_type'     => 'post',
        'has_archive'         => false
    ));
}
add_action('init', 'lr_kinsa_register_post_type');

// Redirect to form builder directly
function lr_kinsa_redirect_to_form_builder() {
    global $pagenow, $typenow;
    
    // Check if we're on the post type page
    if ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'lr_kinsa_form') {
        // Create a new form
        $post_id = wp_insert_post(array(
            'post_title'  => 'Formulario ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'post_type'   => 'lr_kinsa_form',
        ));
        
        // Redirect to the edit page
        wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }
}
add_action('admin_init', 'lr_kinsa_redirect_to_form_builder');

// Custom admin template for form builder
function lr_kinsa_custom_admin_template($template) {
    global $post;
    
    if (isset($post) && $post->post_type === 'lr_kinsa_form') {
        // Remove all meta boxes
        remove_all_actions('add_meta_boxes_lr_kinsa_form');
        
        // Remove the default submit box
        remove_meta_box('submitdiv', 'lr_kinsa_form', 'side');
        
        // Add our custom meta box
        add_meta_box(
            'lr_kinsa_form_builder',
            'Constructor de Formularios',
            'lr_kinsa_form_builder_callback',
            'lr_kinsa_form',
            'normal',
            'high'
        );
        
        // Add shortcode meta box
        add_meta_box(
            'lr_kinsa_form_shortcode',
            'Shortcode',
            'lr_kinsa_form_shortcode_callback',
            'lr_kinsa_form',
            'side',
            'default'
        );
    }
    
    return $template;
}
add_action('add_meta_boxes', 'lr_kinsa_custom_admin_template');

// Form builder callback
function lr_kinsa_form_builder_callback($post) {
    wp_nonce_field('lr_kinsa_save_form', 'lr_kinsa_form_nonce');
    
    // Get form data
    $form_data = get_post_meta($post->ID, '_lr_kinsa_form_data', true);
    $form_settings = get_post_meta($post->ID, '_lr_kinsa_form_settings', true);
    
    if (empty($form_data)) {
        $form_data = array(
            'layout' => array(),
            'fields' => array()
        );
    }
    
    if (empty($form_settings)) {
        $form_settings = array(
            'title' => '',
            'email_to' => get_option('admin_email'),
            'success_message' => 'Gracias por tu mensaje. Ha sido enviado correctamente.',
            'submit_text' => 'Enviar',
            'form_class' => '',
            'form_style' => 'default',
            'correlative_prefix' => 'FORM-',
            'correlative_start' => '00001'
        );
    }
    
    // Get available layout elements
    $layout_elements = array(
        'one_column' => array(
            'icon' => 'dashicons-align-wide',
            'label' => '1 Columna',
            'description' => 'Sección de una columna'
        ),
        'two_columns' => array(
            'icon' => 'dashicons-columns',
            'label' => '2 Columnas',
            'description' => 'Sección de dos columnas'
        )
    );
    
    // Get available field elements
    $field_elements = array(
        'text' => array(
            'icon' => 'dashicons-editor-textcolor',
            'label' => 'Texto',
            'description' => 'Campo de texto simple'
        ),
        'textarea' => array(
            'icon' => 'dashicons-editor-paragraph',
            'label' => 'Área de Texto',
            'description' => 'Campo para texto multilínea'
        ),
        'email' => array(
            'icon' => 'dashicons-email',
            'label' => 'Email',
            'description' => 'Campo para correo electrónico'
        ),
        'number' => array(
            'icon' => 'dashicons-calculator',
            'label' => 'Número',
            'description' => 'Campo para valores numéricos'
        ),
        'tel' => array(
            'icon' => 'dashicons-phone',
            'label' => 'Teléfono',
            'description' => 'Campo para número telefónico'
        ),
        'date' => array(
            'icon' => 'dashicons-calendar-alt',
            'label' => 'Fecha',
            'description' => 'Selector de fecha'
        ),
        'select' => array(
            'icon' => 'dashicons-menu-alt',
            'label' => 'Desplegable',
            'description' => 'Lista desplegable de opciones'
        ),
        'radio' => array(
            'icon' => 'dashicons-marker',
            'label' => 'Radio',
            'description' => 'Botones de opción única'
        ),
        'checkbox' => array(
            'icon' => 'dashicons-yes',
            'label' => 'Casillas',
            'description' => 'Casillas de verificación'
        ),
        'file' => array(
            'icon' => 'dashicons-upload',
            'label' => 'Archivo',
            'description' => 'Carga de archivos'
        ),
        'html' => array(
            'icon' => 'dashicons-editor-code',
            'label' => 'HTML',
            'description' => 'Contenido HTML personalizado'
        ),
        'heading' => array(
            'icon' => 'dashicons-heading',
            'label' => 'Encabezado',
            'description' => 'Título o subtítulo'
        ),
        'paragraph' => array(
            'icon' => 'dashicons-text',
            'label' => 'Párrafo',
            'description' => 'Bloque de texto informativo'
        ),
        'divider' => array(
            'icon' => 'dashicons-minus',
            'label' => 'Divisor',
            'description' => 'Línea divisoria horizontal'
        ),
        'spacer' => array(
            'icon' => 'dashicons-arrow-up-alt2',
            'label' => 'Espaciador',
            'description' => 'Espacio en blanco vertical'
        ),
        'correlative' => array(
            'icon' => 'dashicons-id-alt',
            'label' => 'Correlativo',
            'description' => 'Número correlativo automático'
        ),
        'hidden' => array(
            'icon' => 'dashicons-hidden',
            'label' => 'Campo Oculto',
            'description' => 'Campo invisible para el usuario'
        ),
        'recaptcha' => array(
            'icon' => 'dashicons-shield',
            'label' => 'reCAPTCHA',
            'description' => 'Protección contra spam'
        ),
        'submit' => array(
            'icon' => 'dashicons-yes-alt',
            'label' => 'Botón Enviar',
            'description' => 'Botón para enviar el formulario'
        )
    );
    
    ?>
    <div class="lr-kinsa-builder-wrapper">
        <!-- Top Bar -->
        <div class="lr-kinsa-builder-topbar">
            <div class="lr-kinsa-builder-logo">
                <span class="dashicons dashicons-feedback"></span> Constructor de Formularios
            </div>
				<div class="lr-kinsa-builder-actions">
					<div class="lr-kinsa-shortcode-display">
						<span>Shortcode: </span>
						<input type="text" readonly value="[lr_kinsa_form id=<?php echo $post->ID; ?>]" onclick="this.select();" />
					</div>
					<button type="button" class="button lr-kinsa-toggle-preview">
						<span class="dashicons dashicons-visibility"></span> Vista Previa
					</button>
					<button type="button" id="lr-kinsa-save-form-button" class="button button-primary">
						<span class="dashicons dashicons-saved"></span> Guardar Formulario
					</button>
			</div>
        </div>
        
        <!-- Main Builder Area -->
        <div class="lr-kinsa-builder-main">
            <!-- Left Sidebar - Elements -->
            <div class="lr-kinsa-builder-sidebar lr-kinsa-builder-elements">
                <div class="lr-kinsa-sidebar-header">
                    <h3>Elementos</h3>
                </div>
                <div class="lr-kinsa-sidebar-content">
                    <div class="lr-kinsa-elements-section">
                        <h4>Estructura</h4>
                        <div class="lr-kinsa-elements-grid">
                            <?php foreach ($layout_elements as $type => $element): ?>
                                <div class="lr-kinsa-element-item lr-kinsa-layout-element" data-type="<?php echo esc_attr($type); ?>">
                                    <div class="lr-kinsa-element-icon">
                                        <span class="dashicons <?php echo esc_attr($element['icon']); ?>"></span>
                                    </div>
                                    <div class="lr-kinsa-element-label"><?php echo esc_html($element['label']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="lr-kinsa-elements-section">
                        <h4>Campos</h4>
                        <div class="lr-kinsa-elements-grid">
                            <?php foreach ($field_elements as $type => $element): ?>
                                <div class="lr-kinsa-element-item lr-kinsa-field-element" data-type="<?php echo esc_attr($type); ?>">
                                    <div class="lr-kinsa-element-icon">
                                        <span class="dashicons <?php echo esc_attr($element['icon']); ?>"></span>
                                    </div>
                                    <div class="lr-kinsa-element-label"><?php echo esc_html($element['label']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Center - Form Canvas -->
            <div class="lr-kinsa-builder-canvas">
                <div class="lr-kinsa-canvas-header">
                    <div class="lr-kinsa-canvas-title">
                        <h2>Diseño del Formulario</h2>
                    </div>
                </div>
                
                <div class="lr-kinsa-canvas-content">
                    <div class="lr-kinsa-form-title-container">
                        <input type="text" name="lr_kinsa_form_settings[title]" class="lr-kinsa-form-title-input" placeholder="Título del formulario (opcional)" value="<?php echo esc_attr($form_settings['title']); ?>">
                    </div>
                    
                    <div class="lr-kinsa-form-container" id="lr-kinsa-form-container">
                        <?php if (!empty($form_data['layout'])): ?>
                            <?php foreach ($form_data['layout'] as $layout_id => $layout): ?>
                                <?php if ($layout['type'] === 'one_column'): ?>
                                    <div class="lr-kinsa-layout-item lr-kinsa-one-column" data-layout-id="<?php echo esc_attr($layout_id); ?>">
                                        <div class="lr-kinsa-layout-header">
                                            <div class="lr-kinsa-layout-title">1 Columna</div>
                                            <div class="lr-kinsa-layout-actions">
                                                <span class="lr-kinsa-layout-move dashicons dashicons-move"></span>
                                                <span class="lr-kinsa-layout-delete dashicons dashicons-trash"></span>
                                            </div>
                                        </div>
                                        <div class="lr-kinsa-layout-content">
                                            <div class="lr-kinsa-column" data-column="1">
                                                <?php if (!empty($layout['columns'][1])): ?>
                                                    <?php foreach ($layout['columns'][1] as $field_id): ?>
                                                        <?php if (isset($form_data['fields'][$field_id])): ?>
                                                            <?php $field = $form_data['fields'][$field_id]; ?>
                                                            <div class="lr-kinsa-field-item" data-field-id="<?php echo esc_attr($field_id); ?>" data-field-type="<?php echo esc_attr($field['type']); ?>">
                                                                <div class="lr-kinsa-field-header">
                                                                    <div class="lr-kinsa-field-icon">
                                                                        <span class="dashicons <?php echo esc_attr($field_elements[$field['type']]['icon']); ?>"></span>
                                                                    </div>
                                                                    <div class="lr-kinsa-field-title"><?php echo esc_html($field['label']); ?></div>
                                                                    <div class="lr-kinsa-field-actions">
                                                                        <span class="lr-kinsa-field-move dashicons dashicons-move"></span>
                                                                        <span class="lr-kinsa-field-edit dashicons dashicons-edit"></span>
                                                                        <span class="lr-kinsa-field-duplicate dashicons dashicons-admin-page"></span>
                                                                        <span class="lr-kinsa-field-delete dashicons dashicons-trash"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="lr-kinsa-field-preview">
                                                                    <?php echo lr_kinsa_render_field_preview($field); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($layout['type'] === 'two_columns'): ?>
                                    <div class="lr-kinsa-layout-item lr-kinsa-two-columns" data-layout-id="<?php echo esc_attr($layout_id); ?>">
                                        <div class="lr-kinsa-layout-header">
                                            <div class="lr-kinsa-layout-title">2 Columnas</div>
                                            <div class="lr-kinsa-layout-actions">
                                                <span class="lr-kinsa-layout-move dashicons dashicons-move"></span>
                                                <span class="lr-kinsa-layout-delete dashicons dashicons-trash"></span>
                                            </div>
                                        </div>
                                        <div class="lr-kinsa-layout-content">
                                            <div class="lr-kinsa-column" data-column="1">
                                                <?php if (!empty($layout['columns'][1])): ?>
                                                    <?php foreach ($layout['columns'][1] as $field_id): ?>
                                                        <?php if (isset($form_data['fields'][$field_id])): ?>
                                                            <?php $field = $form_data['fields'][$field_id]; ?>
                                                            <div class="lr-kinsa-field-item" data-field-id="<?php echo esc_attr($field_id); ?>" data-field-type="<?php echo esc_attr($field['type']); ?>">
                                                                <div class="lr-kinsa-field-header">
                                                                    <div class="lr-kinsa-field-icon">
                                                                        <span class="dashicons <?php echo esc_attr($field_elements[$field['type']]['icon']); ?>"></span>
                                                                    </div>
                                                                    <div class="lr-kinsa-field-title"><?php echo esc_html($field['label']); ?></div>
                                                                    <div class="lr-kinsa-field-actions">
                                                                        <span class="lr-kinsa-field-move dashicons dashicons-move"></span>
                                                                        <span class="lr-kinsa-field-edit dashicons dashicons-edit"></span>
                                                                        <span class="lr-kinsa-field-duplicate dashicons dashicons-admin-page"></span>
                                                                        <span class="lr-kinsa-field-delete dashicons dashicons-trash"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="lr-kinsa-field-preview">
                                                                    <?php echo lr_kinsa_render_field_preview($field); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                                            </div>
                                            <div class="lr-kinsa-column" data-column="2">
                                                <?php if (!empty($layout['columns'][2])): ?>
                                                    <?php foreach ($layout['columns'][2] as $field_id): ?>
                                                        <?php if (isset($form_data['fields'][$field_id])): ?>
                                                            <?php $field = $form_data['fields'][$field_id]; ?>
                                                            <div class="lr-kinsa-field-item" data-field-id="<?php echo esc_attr($field_id); ?>" data-field-type="<?php echo esc_attr($field['type']); ?>">
                                                                <div class="lr-kinsa-field-header">
                                                                    <div class="lr-kinsa-field-icon">
                                                                        <span class="dashicons <?php echo esc_attr($field_elements[$field['type']]['icon']); ?>"></span>
                                                                    </div>
                                                                    <div class="lr-kinsa-field-title"><?php echo esc_html($field['label']); ?></div>
                                                                    <div class="lr-kinsa-field-actions">
                                                                        <span class="lr-kinsa-field-move dashicons dashicons-move"></span>
                                                                        <span class="lr-kinsa-field-edit dashicons dashicons-edit"></span>
                                                                        <span class="lr-kinsa-field-duplicate dashicons dashicons-admin-page"></span>
                                                                        <span class="lr-kinsa-field-delete dashicons dashicons-trash"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="lr-kinsa-field-preview">
                                                                    <?php echo lr_kinsa_render_field_preview($field); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="lr-kinsa-empty-form">
                                <div class="lr-kinsa-empty-message">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <p>Arrastra elementos de estructura desde la izquierda para comenzar a crear tu formulario</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lr-kinsa-form-footer">
                    <p>Plugin creado por <a href="https://nebu-lab.com/" target="_blank">Nebu-lab</a></p>
                </div>
            </div>
            
            <!-- Right Sidebar - Field Settings -->
            <div class="lr-kinsa-builder-sidebar lr-kinsa-builder-settings">
                <div class="lr-kinsa-sidebar-header">
                    <h3>
                        Propiedades
                        <span class="lr-kinsa-toggle-properties dashicons dashicons-arrow-up-alt2"></span>
                    </h3>
                </div>
                <div class="lr-kinsa-sidebar-content">
                    <div class="lr-kinsa-settings-tabs">
                        <div class="lr-kinsa-settings-tab active" data-tab="field">Campo</div>
                        <div class="lr-kinsa-settings-tab" data-tab="form">Formulario</div>
                    </div>
                    
                    <!-- Field Settings -->
                    <div class="lr-kinsa-settings-panel active" data-panel="field">
                        <div class="lr-kinsa-no-field-selected">
                            <p>Selecciona un campo para editar sus propiedades</p>
                        </div>
                        
                        <div class="lr-kinsa-field-settings" style="display: none;">
                            <!-- Common Settings -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-label">
                                <label for="lr-kinsa-field-label">Etiqueta</label>
                                <input type="text" id="lr-kinsa-field-label" class="lr-kinsa-field-setting" data-setting="label">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-name">
                                <label for="lr-kinsa-field-name">Nombre del Campo</label>
                                <input type="text" id="lr-kinsa-field-name" class="lr-kinsa-field-setting" data-setting="name">
                                <p class="description">Identificador único para este campo (sin espacios ni caracteres especiales)</p>
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-placeholder">
                                <label for="lr-kinsa-field-placeholder">Placeholder</label>
                                <input type="text" id="lr-kinsa-field-placeholder" class="lr-kinsa-field-setting" data-setting="placeholder">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-default">
                                <label for="lr-kinsa-field-default">Valor Predeterminado</label>
                                <input type="text" id="lr-kinsa-field-default" class="lr-kinsa-field-setting" data-setting="default">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-description">
                                <label for="lr-kinsa-field-description">Descripción</label>
                                <textarea id="lr-kinsa-field-description" class="lr-kinsa-field-setting" data-setting="description" rows="3"></textarea>
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-required">
                                <label>
                                    <input type="checkbox" id="lr-kinsa-field-required" class="lr-kinsa-field-setting" data-setting="required">
                                    Campo obligatorio
                                </label>
                            </div>
                            
                            <!-- Specific Settings for Select, Radio, Checkbox -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-options" style="display: none;">
                                <label>Opciones</label>
                                <div class="lr-kinsa-options-container">
                                    <div class="lr-kinsa-options-list"></div>
                                    <button type="button" class="button lr-kinsa-add-option">Añadir Opción</button>
                                </div>
                            </div>
                            
                            <!-- Specific Settings for Number -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-min" style="display: none;">
                                <label for="lr-kinsa-field-min">Valor Mínimo</label>
                                <input type="number" id="lr-kinsa-field-min" class="lr-kinsa-field-setting" data-setting="min">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-max" style="display: none;">
                                <label for="lr-kinsa-field-max">Valor Máximo</label>
                                <input type="number" id="lr-kinsa-field-max" class="lr-kinsa-field-setting" data-setting="max">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-step" style="display: none;">
                                <label for="lr-kinsa-field-step">Incremento</label>
                                <input type="number" id="lr-kinsa-field-step" class="lr-kinsa-field-setting" data-setting="step">
                            </div>
                            
                            <!-- Specific Settings for HTML -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-html" style="display: none;">
                                <label for="lr-kinsa-field-html">Contenido HTML</label>
                                <textarea id="lr-kinsa-field-html" class="lr-kinsa-field-setting" data-setting="html_content" rows="5"></textarea>
                            </div>
                            
                            <!-- Specific Settings for Heading -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-heading-level" style="display: none;">
                                <label for="lr-kinsa-field-heading-level">Nivel de Encabezado</label>
                                <select id="lr-kinsa-field-heading-level" class="lr-kinsa-field-setting" data-setting="heading_level">
                                    <option value="h1">H1</option>
                                    <option value="h2">H2</option>
                                    <option value="h3">H3</option>
                                    <option value="h4">H4</option>
                                    <option value="h5">H5</option>
                                    <option value="h6">H6</option>
                                </select>
                            </div>
                            
                            <!-- Specific Settings for Spacer -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-height" style="display: none;">
                                <label for="lr-kinsa-field-height">Altura (px)</label>
                                <input type="number" id="lr-kinsa-field-height" class="lr-kinsa-field-setting" data-setting="height" min="1" max="200">
                            </div>
                            
                            <!-- Specific Settings for File -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-allowed-types" style="display: none;">
                                <label for="lr-kinsa-field-allowed-types">Tipos de Archivo Permitidos</label>
                                <input type="text" id="lr-kinsa-field-allowed-types" class="lr-kinsa-field-setting" data-setting="allowed_types">
                                <p class="description">Separados por comas (ej: jpg,png,pdf)</p>
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-max-size" style="display: none;">
                                <label for="lr-kinsa-field-max-size">Tamaño Máximo (MB)</label>
                                <input type="number" id="lr-kinsa-field-max-size" class="lr-kinsa-field-setting" data-setting="max_size" min="1" max="100">
                            </div>
                            
                            <!-- Specific Settings for Correlative -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-correlative-prefix" style="display: none;">
                                <label for="lr-kinsa-field-correlative-prefix">Prefijo</label>
                                <input type="text" id="lr-kinsa-field-correlative-prefix" class="lr-kinsa-field-setting" data-setting="correlative_prefix">
                            </div>
                            
                            <!-- Advanced Settings -->
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-css-class">
                                <label for="lr-kinsa-field-css-class">Clase CSS</label>
                                <input type="text" id="lr-kinsa-field-css-class" class="lr-kinsa-field-setting" data-setting="css_class">
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-visibility">
                                <label for="lr-kinsa-field-visibility">Visibilidad</label>
                                <select id="lr-kinsa-field-visibility" class="lr-kinsa-field-setting" data-setting="visibility">
                                    <option value="always">Siempre visible</option>
                                    <option value="conditional">Condicional</option>
                                </select>
                            </div>
                            
                            <div class="lr-kinsa-setting-group lr-kinsa-setting-conditional" style="display: none;">
                                <label>Condición de Visibilidad</label>
                                <div class="lr-kinsa-conditional-container">
                                    <select class="lr-kinsa-conditional-field">
                                        <option value="">Seleccionar campo</option>
                                    </select>
                                    <select class="lr-kinsa-conditional-operator">
                                        <option value="equals">es igual a</option>
                                        <option value="not_equals">no es igual a</option>
                                        <option value="contains">contiene</option>
                                        <option value="not_contains">no contiene</option>
                                        <option value="greater_than">mayor que</option>
                                        <option value="less_than">menor que</option>
                                    </select>
                                    <input type="text" class="lr-kinsa-conditional-value" placeholder="Valor">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Settings -->
                    <div class="lr-kinsa-settings-panel" data-panel="form">
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-email">Email de Notificación</label>
                            <input type="email" id="lr-kinsa-form-email" name="lr_kinsa_form_settings[email_to]" value="<?php echo esc_attr($form_settings['email_to']); ?>">
                            <p class="description">Las respuestas del formulario se enviarán a esta dirección</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-success">Mensaje de Éxito</label>
                            <textarea id="lr-kinsa-form-success" name="lr_kinsa_form_settings[success_message]" rows="3"><?php echo esc_textarea($form_settings['success_message']); ?></textarea>
                            <p class="description">Mensaje que se mostrará después de enviar el formulario</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-submit">Texto del Botón</label>
                            <input type="text" id="lr-kinsa-form-submit" name="lr_kinsa_form_settings[submit_text]" value="<?php echo esc_attr($form_settings['submit_text']); ?>">
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-class">Clase CSS Personalizada</label>
                            <input type="text" id="lr-kinsa-form-class" name="lr_kinsa_form_settings[form_class]" value="<?php echo esc_attr($form_settings['form_class']); ?>">
                            <p class="description">Clases CSS adicionales para el formulario</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-style">Estilo del Formulario</label>
                            <select id="lr-kinsa-form-style" name="lr_kinsa_form_settings[form_style]">
                                <option value="default" <?php selected($form_settings['form_style'], 'default'); ?>>Predeterminado</option>
                                <option value="flat" <?php selected($form_settings['form_style'], 'flat'); ?>>Plano</option>
                                <option value="material" <?php selected($form_settings['form_style'], 'material'); ?>>Material Design</option>
                                <option value="minimal" <?php selected($form_settings['form_style'], 'minimal'); ?>>Minimalista</option>
                            </select>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-correlative-prefix">Prefijo para Correlativos</label>
                            <input type="text" id="lr-kinsa-correlative-prefix" name="lr_kinsa_form_settings[correlative_prefix]" value="<?php echo esc_attr($form_settings['correlative_prefix']); ?>">
                            <p class="description">Texto que aparecerá antes del número correlativo</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-correlative-start">Número Inicial para Correlativos</label>
                            <input type="text" id="lr-kinsa-correlative-start" name="lr_kinsa_form_settings[correlative_start]" value="<?php echo esc_attr($form_settings['correlative_start']); ?>">
                            <p class="description">Número desde el que comenzará la numeración</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label for="lr-kinsa-form-redirect">URL de Redirección</label>
                            <input type="url" id="lr-kinsa-form-redirect" name="lr_kinsa_form_settings[redirect_url]" value="<?php echo isset($form_settings['redirect_url']) ? esc_url($form_settings['redirect_url']) : ''; ?>">
                            <p class="description">Opcional: URL a la que se redirigirá después de enviar el formulario</p>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label>
                                <input type="checkbox" id="lr-kinsa-form-save-entries" name="lr_kinsa_form_settings[save_entries]" <?php checked(isset($form_settings['save_entries']) ? $form_settings['save_entries'] : true); ?>>
                                Guardar entradas en la base de datos
                            </label>
                        </div>
                        
                        <div class="lr-kinsa-setting-group">
                            <label>
                                <input type="checkbox" id="lr-kinsa-form-enable-recaptcha" name="lr_kinsa_form_settings[enable_recaptcha]" <?php checked(isset($form_settings['enable_recaptcha']) ? $form_settings['enable_recaptcha'] : false); ?>>
                                Habilitar reCAPTCHA
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div class="lr-kinsa-preview-modal" style="display: none;">
            <div class="lr-kinsa-preview-modal-content">
                <div class="lr-kinsa-preview-header">
                    <h3>Vista Previa del Formulario</h3>
                    <button type="button" class="lr-kinsa-close-preview">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="lr-kinsa-preview-body">
                    <iframe id="lr-kinsa-preview-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
        
        <!-- Field Templates -->
        <div id="lr-kinsa-templates" style="display: none;">
            <!-- Option Template -->
            <div class="lr-kinsa-option-template">
                <div class="lr-kinsa-option-item">
                    <span class="lr-kinsa-option-drag dashicons dashicons-menu"></span>
                    <input type="text" class="lr-kinsa-option-value" value="" placeholder="Valor">
                    <input type="text" class="lr-kinsa-option-label" value="" placeholder="Etiqueta">
                    <button type="button" class="lr-kinsa-remove-option">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
            
            <!-- One Column Layout Template -->
            <div class="lr-kinsa-one-column-template">
                <div class="lr-kinsa-layout-item lr-kinsa-one-column" data-layout-id="LAYOUT_ID">
                    <div class="lr-kinsa-layout-header">
                        <div class="lr-kinsa-layout-title">1 Columna</div>
                        <div class="lr-kinsa-layout-actions">
                            <span class="lr-kinsa-layout-move dashicons dashicons-move"></span>
                            <span class="lr-kinsa-layout-delete dashicons dashicons-trash"></span>
                        </div>
                    </div>
                    <div class="lr-kinsa-layout-content">
                        <div class="lr-kinsa-column" data-column="1">
                            <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Two Columns Layout Template -->
            <div class="lr-kinsa-two-columns-template">
                <div class="lr-kinsa-layout-item lr-kinsa-two-columns" data-layout-id="LAYOUT_ID">
                    <div class="lr-kinsa-layout-header">
                        <div class="lr-kinsa-layout-title">2 Columnas</div>
                        <div class="lr-kinsa-layout-actions">
                            <span class="lr-kinsa-layout-move dashicons dashicons-move"></span>
                            <span class="lr-kinsa-layout-delete dashicons dashicons-trash"></span>
                        </div>
                    </div>
                    <div class="lr-kinsa-layout-content">
                        <div class="lr-kinsa-column" data-column="1">
                            <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                        </div>
                        <div class="lr-kinsa-column" data-column="2">
                            <div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Field Item Template -->
            <div class="lr-kinsa-field-template">
                <div class="lr-kinsa-field-item" data-field-id="FIELD_ID" data-field-type="FIELD_TYPE">
                    <div class="lr-kinsa-field-header">
                        <div class="lr-kinsa-field-icon">
                            <span class="dashicons FIELD_ICON"></span>
                        </div>
                        <div class="lr-kinsa-field-title">FIELD_LABEL</div>
                        <div class="lr-kinsa-field-actions">
                            <span class="lr-kinsa-field-move dashicons dashicons-move"></span>
                            <span class="lr-kinsa-field-edit dashicons dashicons-edit"></span>
                            <span class="lr-kinsa-field-duplicate dashicons dashicons-admin-page"></span>
                            <span class="lr-kinsa-field-delete dashicons dashicons-trash"></span>
                        </div>
                    </div>
                    <div class="lr-kinsa-field-preview">
                        FIELD_PREVIEW
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden Form Data -->
        <input type="hidden" id="lr-kinsa-form-data" name="lr_kinsa_form_data" value="<?php echo esc_attr(json_encode($form_data)); ?>">
    </div>
    
    <style id="lr-kinsa-admin-styles">
        /* Reset WP Admin Styles */
        #poststuff {
            padding-top: 0;
        }
        
        #post-body-content {
            margin-bottom: 0;
        }
        
        /* Hide unnecessary elements */
        .wrap h1.wp-heading-inline,
        .page-title-action,
        #post-body-content > .postbox:not(#lr_kinsa_form_builder),
        #titlediv,
        #normal-sortables > .postbox:not(#lr_kinsa_form_builder),
        #submitdiv {
            display: none !important;
        }
        
        /* Full-width builder */
        #poststuff #post-body.columns-2 {
            margin-right: 0;
        }
        
        #post-body.columns-2 #postbox-container-1 {
            margin-right: 0;
            width: 100%;
            float: none;
        }
        
        #postbox-container-2 {
            width: 100% !important;
        }
        
        /* Builder Styles */
        .lr-kinsa-builder-wrapper {
            position: fixed;
            top: 32px;
            left: 160px;
            right: 0;
            bottom: 0;
            background: #f0f0f1;
            z-index: 99999;
            display: flex;
            flex-direction: column;
        }
        
        @media (max-width: 960px) {
            .lr-kinsa-builder-wrapper {
                left: 36px;
            }
        }
        
        /* Top Bar */
        .lr-kinsa-builder-topbar {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lr-kinsa-builder-logo {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .lr-kinsa-builder-logo .dashicons {
            margin-right: 8px;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .lr-kinsa-builder-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .lr-kinsa-shortcode-display {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-right: 10px;
        }
        
        .lr-kinsa-shortcode-display input {
            width: 250px;
            background-color: #f0f0f1;
            cursor: pointer;
        }
        
        .lr-kinsa-builder-actions .button {
            display: flex;
            align-items: center;
        }
        
        .lr-kinsa-builder-actions .dashicons {
            margin-right: 5px;
        }
        
        /* Main Builder Area */
        .lr-kinsa-builder-main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* Sidebar */
        .lr-kinsa-builder-sidebar {
            width: 300px;
            background: #fff;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .lr-kinsa-sidebar-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lr-kinsa-sidebar-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: space-between;
        }
        
        .lr-kinsa-toggle-properties {
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .lr-kinsa-toggle-properties.collapsed {
            transform: rotate(180deg);
        }
        
        .lr-kinsa-sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        /* Elements Section */
        .lr-kinsa-elements-section {
            margin-bottom: 20px;
        }
        
        .lr-kinsa-elements-section h4 {
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .lr-kinsa-elements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .lr-kinsa-element-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: move;
            background: #f9f9f9;
            transition: all 0.2s;
            text-align: center;
        }
        
        .lr-kinsa-element-item:hover {
            border-color: #2271b1;
            background: #f0f7ff;
        }
        
        .lr-kinsa-element-icon {
            margin-bottom: 5px;
            color: #555;
        }
        
        .lr-kinsa-element-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        
        .lr-kinsa-element-label {
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Canvas */
        .lr-kinsa-builder-canvas {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .lr-kinsa-canvas-header {
            padding: 15px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lr-kinsa-canvas-title h2 {
            margin: 0;
            font-size: 18px;
        }
        
        /* Canvas Content */
        .lr-kinsa-canvas-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .lr-kinsa-form-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .lr-kinsa-form-title-container {
            margin-bottom: 20px;
            width: 100%;
        }
        
        .lr-kinsa-form-title-input {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            border: 1px dashed #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .lr-kinsa-form-title-input:focus {
            border-color: #2271b1;
            outline: none;
        }
        
        .lr-kinsa-empty-form {
            padding: 30px;
            text-align: center;
            border: 2px dashed #ddd;
            border-radius: 5px;
        }
        
        .lr-kinsa-empty-message {
            color: #888;
        }
        
        .lr-kinsa-empty-message .dashicons {
            font-size: 30px;
            width: 30px;
            height: 30px;
            margin-bottom: 10px;
        }
        
        /* Layout Items */
        .lr-kinsa-layout-item {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .lr-kinsa-layout-header {
            padding: 10px;
            background: #f0f0f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lr-kinsa-layout-title {
            font-weight: 600;
        }
        
        .lr-kinsa-layout-actions {
            display: flex;
            gap: 5px;
        }
        
        .lr-kinsa-layout-actions .dashicons {
            cursor: pointer;
            color: #777;
            transition: color 0.2s;
        }
        
        .lr-kinsa-layout-actions .dashicons:hover {
            color: #2271b1;
        }
        
        .lr-kinsa-layout-delete:hover {
            color: #dc3232 !important;
        }
        
        .lr-kinsa-layout-content {
            padding: 15px;
            display: flex;
            gap: 15px;
        }
        
        .lr-kinsa-one-column .lr-kinsa-layout-content {
            flex-direction: column;
        }
        
        .lr-kinsa-two-columns .lr-kinsa-layout-content {
            flex-direction: row;
        }
        
        .lr-kinsa-column {
            flex: 1;
            min-height: 100px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            padding: 10px;
        }
        
        .lr-kinsa-column-placeholder {
            color: #888;
            text-align: center;
            padding: 20px 0;
            font-style: italic;
        }
        
        /* Field Items */
        .lr-kinsa-field-item {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        
        .lr-kinsa-field-header {
            padding: 8px 10px;
            background: #f0f0f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        
        .lr-kinsa-field-icon {
            margin-right: 10px;
            color: #555;
        }
        
        .lr-kinsa-field-title {
            flex: 1;
            font-weight: 600;
        }
        
        .lr-kinsa-field-actions {
            display: flex;
            gap: 5px;
        }
        
        .lr-kinsa-field-actions .dashicons {
            cursor: pointer;
            color: #777;
            transition: color 0.2s;
        }
        
        .lr-kinsa-field-actions .dashicons:hover {
            color: #2271b1;
        }
        
        .lr-kinsa-field-delete:hover {
            color: #dc3232 !important;
        }
        
        .lr-kinsa-field-preview {
            padding: 10px;
        }
        
        /* Field Preview Styles */
        .lr-kinsa-preview-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .lr-kinsa-preview-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f5f5f5;
        }
        
        .lr-kinsa-preview-textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f5f5f5;
            min-height: 100px;
        }
        
        .lr-kinsa-preview-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f5f5f5;
        }
        
        .lr-kinsa-preview-radio,
        .lr-kinsa-preview-checkbox {
            margin-top: 5px;
        }
        
        .lr-kinsa-preview-radio-item,
        .lr-kinsa-preview-checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .lr-kinsa-preview-radio-input,
        .lr-kinsa-preview-checkbox-input {
            margin-right: 8px;
        }
        
        .lr-kinsa-preview-html {
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .lr-kinsa-preview-heading {
            margin: 0;
            font-weight: 600;
        }
        
        .lr-kinsa-preview-paragraph {
            margin: 0;
        }
        
        .lr-kinsa-preview-divider {
            border-top: 1px solid #ddd;
            margin: 10px 0;
        }
        
        .lr-kinsa-preview-spacer {
            height: 20px;
        }
        
        .lr-kinsa-preview-correlative {
            padding: 8px;
            background: #f0f0f1;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .lr-kinsa-preview-submit {
            background: #2271b1;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            cursor: not-allowed;
        }
        
        /* Form Footer */
        .lr-kinsa-form-footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        
        .lr-kinsa-form-footer a {
            color: #2271b1;
            text-decoration: none;
        }
        
        .lr-kinsa-form-footer a:hover {
            text-decoration: underline;
        }
        
        /* Settings Panel */
        .lr-kinsa-settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 15px;
        }
        
        .lr-kinsa-settings-tab {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        
        .lr-kinsa-settings-tab.active {
            border-bottom-color: #2271b1;
            color: #2271b1;
        }
        
        .lr-kinsa-settings-panel {
            display: none;
        }
        
        .lr-kinsa-settings-panel.active {
            display: block;
        }
        
        .lr-kinsa-no-field-selected {
            text-align: center;
            padding: 20px;
            color: #888;
        }
        
        .lr-kinsa-setting-group {
            margin-bottom: 15px;
        }
        
        .lr-kinsa-setting-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .lr-kinsa-setting-group input[type="text"],
        .lr-kinsa-setting-group input[type="email"],
        .lr-kinsa-setting-group input[type="number"],
        .lr-kinsa-setting-group input[type="url"],
        .lr-kinsa-setting-group select,
        .lr-kinsa-setting-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .lr-kinsa-setting-group .description {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        
        /* Options */
        .lr-kinsa-options-container {
            margin-top: 10px;
        }
        
        .lr-kinsa-options-list {
            margin-bottom: 10px;
        }
        
        .lr-kinsa-option-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 4px;
        }
        
        .lr-kinsa-option-drag {
            cursor: move;
            margin-right: 5px;
            color: #777;
        }
        
        .lr-kinsa-option-value,
        .lr-kinsa-option-label {
            flex: 1;
            margin: 0 5px;
        }
        
        .lr-kinsa-remove-option {
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            padding: 0;
        }
        
        .lr-kinsa-remove-option:hover {
            color: #dc3232;
        }
        
        /* Conditional Logic */
        .lr-kinsa-conditional-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        /* Preview Modal */
        .lr-kinsa-preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lr-kinsa-preview-modal-content {
            background: #fff;
            border-radius: 5px;
            width: 80%;
            max-width: 1000px;
            height: 80%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .lr-kinsa-preview-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lr-kinsa-preview-header h3 {
            margin: 0;
        }
        
        .lr-kinsa-close-preview {
            background: none;
            border: none;
            cursor: pointer;
            color: #777;
        }
        
        .lr-kinsa-preview-body {
            flex: 1;
            overflow: hidden;
        }
        
        #lr-kinsa-preview-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* UI Sortable and Droppable Styles */
        .ui-sortable-placeholder {
            border: 2px dashed #2271b1;
            background-color: rgba(34, 113, 177, 0.1);
            visibility: visible !important;
            margin-bottom: 10px;
            border-radius: 4px;
            height: 50px;
        }
        
        .ui-sortable-helper {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .ui-droppable-active {
            border-color: #2271b1;
        }
        
        .ui-droppable-hover {
            background-color: rgba(34, 113, 177, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .lr-kinsa-builder-sidebar {
                width: 250px;
            }
        }
        
        @media (max-width: 782px) {
            .lr-kinsa-builder-wrapper {
                top: 46px;
            }
            
            .lr-kinsa-builder-main {
                flex-direction: column;
            }
            
            .lr-kinsa-builder-sidebar {
                width: 100%;
                height: 300px;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
        }
    </style>
    
<script>
jQuery(document).ready(function($) {
    // Variables
    var formData = {
        layout: {},
        fields: {}
    };
    var layoutCounter = 0;
    var fieldCounter = 0;
    var selectedField = null;
    var selectedLayout = null;
    
    // Initialize form data from hidden input
    try {
        var savedFormData = JSON.parse($('#lr-kinsa-form-data').val());
        if (savedFormData) {
            formData = savedFormData;
            
            // Find the highest layout and field IDs to continue the counters
            for (var layoutId in formData.layout) {
                var numericId = parseInt(layoutId.replace('layout_', ''));
                if (numericId > layoutCounter) {
                    layoutCounter = numericId;
                }
            }
            
            for (var fieldId in formData.fields) {
                var numericId = parseInt(fieldId.replace('field_', ''));
                if (numericId > fieldCounter) {
                    fieldCounter = numericId;
                }
            }
            
            layoutCounter++;
            fieldCounter++;
        }
    } catch (e) {
        console.error('Error parsing form data:', e);
    }
    
    // Make layout elements draggable
    $('.lr-kinsa-layout-element').draggable({
        helper: 'clone',
        revert: 'invalid',
        connectToSortable: false,
        start: function(event, ui) {
            ui.helper.css('width', $(this).width());
            ui.helper.css('height', $(this).height());
        }
    });
    
    // Make field elements draggable
    $('.lr-kinsa-field-element').draggable({
        helper: 'clone',
        revert: 'invalid',
        connectToSortable: false,
        start: function(event, ui) {
            ui.helper.css('width', $(this).width());
            ui.helper.css('height', $(this).height());
        }
    });
    
    // Make form container droppable for layouts
    $('#lr-kinsa-form-container').droppable({
        accept: '.lr-kinsa-layout-element',
        hoverClass: 'ui-droppable-hover',
        drop: function(event, ui) {
            var layoutType = ui.draggable.data('type');
            var layoutId = 'layout_' + Date.now();
            
            // Create a new layout object
            var newLayout = {
                id: layoutId,
                type: layoutType,
                columns: {}
            };
            
            // Initialize columns
            if (layoutType === 'one_column') {
                newLayout.columns[1] = [];
            } else if (layoutType === 'two_columns') {
                newLayout.columns[1] = [];
                newLayout.columns[2] = [];
            }
            
            // Add to form data
            formData.layout[layoutId] = newLayout;
            
            // Get layout template
            var template = '';
            if (layoutType === 'one_column') {
                template = $('.lr-kinsa-one-column-template').html();
            } else if (layoutType === 'two_columns') {
                template = $('.lr-kinsa-two-columns-template').html();
            }
            
            // Replace layout ID
            template = template.replace(/LAYOUT_ID/g, layoutId);
            
            // Remove empty form message if it exists
            $('.lr-kinsa-empty-form').remove();
            
            // Append the layout to the form container
            $(this).append(template);
            
            // Initialize the columns as droppable
            initializeColumns();
            
            // Make layout sortable
            $('#lr-kinsa-form-container').sortable({
                handle: '.lr-kinsa-layout-move',
                items: '.lr-kinsa-layout-item',
                placeholder: 'ui-sortable-placeholder',
                update: function() {
                    updateFormData();
                }
            });
            
            // Update form data
            updateFormData();
        }
    });
    
    // Initialize existing columns as droppable
    initializeColumns();
    
    // Initialize function for columns
    function initializeColumns() {
        $('.lr-kinsa-column').droppable({
            accept: '.lr-kinsa-field-element',
            hoverClass: 'ui-droppable-hover',
            drop: function(event, ui) {
                var fieldType = ui.draggable.data('type');
                var fieldId = 'field_' + Date.now();
                var layoutId = $(this).closest('.lr-kinsa-layout-item').data('layout-id');
                var column = $(this).data('column');
                
                // Create a new field object
                var newField = createDefaultField(fieldType, fieldId);
                
                // Add to form data
                formData.fields[fieldId] = newField;
                
                // Add to layout
                formData.layout[layoutId].columns[column].push(fieldId);
                
                // Create field HTML
                var fieldHTML = createFieldHTML(fieldId, fieldType, newField);
                
                // Remove column placeholder if it exists
                $(this).find('.lr-kinsa-column-placeholder').remove();
                
                // Append the field to the column
                $(this).append(fieldHTML);
                
                // Make fields sortable within columns
                $(this).sortable({
                    items: '.lr-kinsa-field-item',
                    handle: '.lr-kinsa-field-move',
                    connectWith: '.lr-kinsa-column',
                    placeholder: 'ui-sortable-placeholder',
                    update: function() {
                        updateFieldOrder($(this));
                    }
                });
                
                // Update form data
                updateFormData();
            }
        }).sortable({
            items: '.lr-kinsa-field-item',
            handle: '.lr-kinsa-field-move',
            connectWith: '.lr-kinsa-column',
            placeholder: 'ui-sortable-placeholder',
            update: function() {
                updateFieldOrder($(this));
            }
        });
    }
    
    // Update field order in a column
    function updateFieldOrder(column) {
        var layoutId = column.closest('.lr-kinsa-layout-item').data('layout-id');
        var columnNum = column.data('column');
        var fields = [];
        
        column.find('.lr-kinsa-field-item').each(function() {
            var fieldId = $(this).data('field-id');
            fields.push(fieldId);
        });
        
        // Update layout data
        formData.layout[layoutId].columns[columnNum] = fields;
        
        // Update form data
        updateFormData();
    }
    
    // Delete layout
    $(document).on('click', '.lr-kinsa-layout-delete', function() {
        if (confirm('¿Estás seguro de que quieres eliminar esta sección?')) {
            var layoutItem = $(this).closest('.lr-kinsa-layout-item');
            var layoutId = layoutItem.data('layout-id');
            
            // Remove layout from form data
            if (formData.layout[layoutId]) {
                // Remove fields in this layout
                for (var column in formData.layout[layoutId].columns) {
                    formData.layout[layoutId].columns[column].forEach(function(fieldId) {
                        delete formData.fields[fieldId];
                    });
                }
                
                delete formData.layout[layoutId];
            }
            
            // Remove layout from DOM
            layoutItem.remove();
            
            // If no layouts left, show empty message
            if ($('.lr-kinsa-layout-item').length === 0) {
                $('#lr-kinsa-form-container').html('<div class="lr-kinsa-empty-form"><div class="lr-kinsa-empty-message"><span class="dashicons dashicons-plus-alt"></span><p>Arrastra elementos de estructura desde la izquierda para comenzar a crear tu formulario</p></div></div>');
            }
            
            // Update form data
            updateFormData();
        }
    });
    
    // Edit field
    $(document).on('click', '.lr-kinsa-field-edit', function() {
        var fieldItem = $(this).closest('.lr-kinsa-field-item');
        var fieldId = fieldItem.data('field-id');
        var fieldType = fieldItem.data('field-type');
        
        // Set selected field
        selectedField = fieldId;
        
        // Show field settings
        $('.lr-kinsa-no-field-selected').hide();
        $('.lr-kinsa-field-settings').show();
        
        // Set active field
        $('.lr-kinsa-field-item').removeClass('active');
        fieldItem.addClass('active');
        
        // Load field settings
        loadFieldSettings(fieldId, fieldType);
        
        // Activate field tab
        $('.lr-kinsa-settings-tab[data-tab="field"]').click();
    });
    
    // Delete field
    $(document).on('click', '.lr-kinsa-field-delete', function() {
        if (confirm('¿Estás seguro de que quieres eliminar este campo?')) {
            var fieldItem = $(this).closest('.lr-kinsa-field-item');
            var fieldId = fieldItem.data('field-id');
            var layoutItem = fieldItem.closest('.lr-kinsa-layout-item');
            var layoutId = layoutItem.data('layout-id');
            var column = fieldItem.closest('.lr-kinsa-column').data('column');
            
            // Remove field from form data
            if (formData.fields[fieldId]) {
                delete formData.fields[fieldId];
            }
            
            // Remove field from layout
            if (formData.layout[layoutId] && formData.layout[layoutId].columns[column]) {
                var index = formData.layout[layoutId].columns[column].indexOf(fieldId);
                if (index !== -1) {
                    formData.layout[layoutId].columns[column].splice(index, 1);
                }
            }
            
            // Remove field from DOM
            fieldItem.remove();
            
            // If no fields left in column, show placeholder
            if (layoutItem.find('.lr-kinsa-column[data-column="' + column + '"] .lr-kinsa-field-item').length === 0) {
                layoutItem.find('.lr-kinsa-column[data-column="' + column + '"]').append('<div class="lr-kinsa-column-placeholder">Arrastra elementos aquí</div>');
            }
            
            // Hide field settings if the active field was deleted
            if (fieldItem.hasClass('active')) {
                $('.lr-kinsa-field-settings').hide();
                $('.lr-kinsa-no-field-selected').show();
            }
            
            // Update form data
            updateFormData();
        }
    });
    
    // Duplicate field
    $(document).on('click', '.lr-kinsa-field-duplicate', function() {
        var fieldItem = $(this).closest('.lr-kinsa-field-item');
        var fieldId = fieldItem.data('field-id');
        var fieldType = fieldItem.data('field-type');
        var layoutItem = fieldItem.closest('.lr-kinsa-layout-item');
        var layoutId = layoutItem.data('layout-id');
        var column = fieldItem.closest('.lr-kinsa-column').data('column');
        
        // Create new field ID
        var newFieldId = 'field_' + Date.now();
        
        // Clone field data
        var newField = JSON.parse(JSON.stringify(formData.fields[fieldId]));
        newField.id = newFieldId;
        
        // Add to form data
        formData.fields[newFieldId] = newField;
        
        // Add to layout
        formData.layout[layoutId].columns[column].push(newFieldId);
        
        // Create field HTML
        var fieldHTML = createFieldHTML(newFieldId, fieldType, newField);
        
        // Insert after the original field
        fieldItem.after(fieldHTML);
        
        // Update form data
        updateFormData();
    });
    
    // Update field settings
    $(document).on('change', '.lr-kinsa-field-setting', function() {
        var setting = $(this).data('setting');
        var value = $(this).val();
        var isCheckbox = $(this).attr('type') === 'checkbox';
        
        if (selectedField && formData.fields[selectedField]) {
            // Update field data
            if (isCheckbox) {
                formData.fields[selectedField][setting] = $(this).prop('checked');
            } else {
                formData.fields[selectedField][setting] = value;
            }
            
            // Update field preview
            var fieldItem = $('.lr-kinsa-field-item[data-field-id="' + selectedField + '"]');
            
            // Update field title if label changed
            if (setting === 'label') {
                fieldItem.find('.lr-kinsa-field-title').text(value);
            }
            
            // Update field preview
            fieldItem.find('.lr-kinsa-field-preview').html(renderFieldPreview(formData.fields[selectedField]));
            
            // Update form data
            updateFormData();
        }
    });
    
    // Add option
    $(document).on('click', '.lr-kinsa-add-option', function() {
        var optionsList = $('.lr-kinsa-options-list');
        
        if (selectedField && formData.fields[selectedField]) {
            // Add option template
            var optionTemplate = $('.lr-kinsa-option-template').html();
            optionsList.append(optionTemplate);
            
            // Focus on new option
            optionsList.find('.lr-kinsa-option-item:last-child .lr-kinsa-option-value').focus();
            
            // Update options
            updateOptions();
        }
    });
    
    // Remove option
    $(document).on('click', '.lr-kinsa-remove-option', function() {
        $(this).closest('.lr-kinsa-option-item').remove();
        updateOptions();
    });
    
    // Update option value
    $(document).on('input', '.lr-kinsa-option-value, .lr-kinsa-option-label', function() {
        updateOptions();
    });
    
    // Make options sortable
    $('.lr-kinsa-options-list').sortable({
        handle: '.lr-kinsa-option-drag',
        update: function() {
            updateOptions();
        }
    });
    
    // Toggle properties panel
    $('.lr-kinsa-toggle-properties').on('click', function() {
        $(this).toggleClass('collapsed');
        $('.lr-kinsa-sidebar-content').slideToggle(300);
    });
    
    // Settings tabs
    $('.lr-kinsa-settings-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.lr-kinsa-settings-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding panel
        $('.lr-kinsa-settings-panel').removeClass('active');
        $('.lr-kinsa-settings-panel[data-panel="' + tab + '"]').addClass('active');
    });
    
    // Toggle preview modal
    $('.lr-kinsa-toggle-preview').on('click', function() {
        // Generate preview HTML
        var previewHTML = generatePreviewHTML();
        
        // Set iframe content
        var iframe = document.getElementById('lr-kinsa-preview-frame');
        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(previewHTML);
        iframe.contentWindow.document.close();
        
        // Show modal
        $('.lr-kinsa-preview-modal').show();
    });
    
    // Close preview modal
    $('.lr-kinsa-close-preview').on('click', function() {
        $('.lr-kinsa-preview-modal').hide();
    });
    
    // Close modal on outside click
    $('.lr-kinsa-preview-modal').on('click', function(e) {
        if ($(e.target).hasClass('lr-kinsa-preview-modal')) {
            $(this).hide();
        }
    });
    
    // Functions
    
    // Create default field based on type
    function createDefaultField(fieldType, fieldId) {
        var field = {
            id: fieldId,
            type: fieldType,
            label: getDefaultLabel(fieldType),
            name: getDefaultName(fieldType, fieldId),
            placeholder: getDefaultPlaceholder(fieldType),
            required: false,
            css_class: ''
        };
        
        // Add type-specific properties
        switch (fieldType) {
            case 'select':
            case 'radio':
            case 'checkbox':
                field.options = [
                    { value: 'option1', label: 'Opción 1' },
                    { value: 'option2', label: 'Opción 2' },
                    { value: 'option3', label: 'Opción 3' }
                ];
                break;
            case 'number':
                field.min = '';
                field.max = '';
                field.step = '1';
                break;
            case 'html':
                field.html_content = '<p>Contenido HTML personalizado</p>';
                break;
            case 'heading':
                field.heading_level = 'h3';
                break;
            case 'spacer':
                field.height = '20';
                break;
            case 'file':
                field.allowed_types = 'jpg,png,pdf';
                field.max_size = '5';
                break;
            case 'correlative':
                field.correlative_prefix = 'FORM-';
                break;
        }
        
        return field;
    }
    
    // Get default label based on field type
    function getDefaultLabel(fieldType) {
        var labels = {
            'text': 'Texto',
            'textarea': 'Área de Texto',
            'email': 'Correo Electrónico',
            'number': 'Número',
            'tel': 'Teléfono',
            'date': 'Fecha',
            'select': 'Seleccionar Opción',
            'radio': 'Opciones',
            'checkbox': 'Casillas de Verificación',
            'file': 'Subir Archivo',
            'html': 'Contenido HTML',
            'heading': 'Encabezado',
            'paragraph': 'Párrafo',
            'divider': 'Divisor',
            'spacer': 'Espaciador',
            'correlative': 'Número de Formulario',
            'hidden': 'Campo Oculto',
            'recaptcha': 'Verificación reCAPTCHA',
            'submit': 'Enviar Formulario'
        };
        
        return labels[fieldType] || 'Campo';
    }
    
    // Get default name based on field type
    function getDefaultName(fieldType, fieldId) {
        var baseName = fieldType;
        var numericId = fieldId.replace('field_', '');
        return baseName + '_' + numericId;
    }
    
    // Get default placeholder based on field type
    function getDefaultPlaceholder(fieldType) {
        var placeholders = {
            'text': 'Ingrese texto',
            'textarea': 'Ingrese texto aquí',
            'email': 'ejemplo@correo.com',
            'number': '0',
            'tel': '(123) 456-7890',
            'date': '',
            'select': 'Seleccione una opción'
        };
        
        return placeholders[fieldType] || '';
    }
    
    // Create field HTML
    function createFieldHTML(fieldId, fieldType, field) {
        var fieldElements = {
            'text': { icon: 'dashicons-editor-textcolor', label: 'Texto' },
            'textarea': { icon: 'dashicons-editor-paragraph', label: 'Área de Texto' },
            'email': { icon: 'dashicons-email', label: 'Email' },
            'number': { icon: 'dashicons-calculator', label: 'Número' },
            'tel': { icon: 'dashicons-phone', label: 'Teléfono' },
            'date': { icon: 'dashicons-calendar-alt', label: 'Fecha' },
            'select': { icon: 'dashicons-menu-alt', label: 'Desplegable' },
            'radio': { icon: 'dashicons-marker', label: 'Radio' },
            'checkbox': { icon: 'dashicons-yes', label: 'Casillas' },
            'file': { icon: 'dashicons-upload', label: 'Archivo' },
            'html': { icon: 'dashicons-editor-code', label: 'HTML' },
            'heading': { icon: 'dashicons-heading', label: 'Encabezado' },
            'paragraph': { icon: 'dashicons-text', label: 'Párrafo' },
            'divider': { icon: 'dashicons-minus', label: 'Divisor' },
            'spacer': { icon: 'dashicons-arrow-up-alt2', label: 'Espaciador' },
            'correlative': { icon: 'dashicons-id-alt', label: 'Correlativo' },
            'hidden': { icon: 'dashicons-hidden', label: 'Campo Oculto' },
            'recaptcha': { icon: 'dashicons-shield', label: 'reCAPTCHA' },
            'submit': { icon: 'dashicons-yes-alt', label: 'Botón Enviar' }
        };
        
        var template = $('.lr-kinsa-field-template').html();
        template = template.replace(/FIELD_ID/g, fieldId);
        template = template.replace(/FIELD_TYPE/g, fieldType);
        template = template.replace(/FIELD_ICON/g, fieldElements[fieldType].icon);
        template = template.replace(/FIELD_LABEL/g, field.label);
        template = template.replace(/FIELD_PREVIEW/g, renderFieldPreview(field));
        
        return template;
    }
    
    // Render field preview
    function renderFieldPreview(field) {
        var preview = '';
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
            case 'date':
                preview = '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                preview += '<input type="' + field.type + '" class="lr-kinsa-preview-input" placeholder="' + (isset(field.placeholder) ? field.placeholder : '') + '" disabled>';
                break;
            case 'textarea':
                preview = '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                preview += '<textarea class="lr-kinsa-preview-textarea" placeholder="' + (isset(field.placeholder) ? field.placeholder : '') + '" disabled></textarea>';
                break;
            case 'select':
                preview = '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                preview += '<select class="lr-kinsa-preview-select" disabled>';
                preview += '<option value="">' + (isset(field.placeholder) ? field.placeholder : 'Seleccionar...') + '</option>';
                if (!empty(field.options)) {
                    for (var i = 0; i < field.options.length; i++) {
                        var option = field.options[i];
                        preview += '<option value="' + option.value + '">' + option.label + '</option>';
                    }
                }
                preview += '</select>';
                break;
            case 'radio':
                preview = '<div class="lr-kinsa-preview-radio">';
                preview += '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                if (!empty(field.options)) {
                    for (var i = 0; i < field.options.length; i++) {
                        var option = field.options[i];
                        preview += '<div class="lr-kinsa-preview-radio-item">';
                        preview += '<input type="radio" class="lr-kinsa-preview-radio-input" disabled>';
                        preview += '<label>' + option.label + '</label>';
                        preview += '</div>';
                    }
                }
                preview += '</div>';
                break;
            case 'checkbox':
                preview = '<div class="lr-kinsa-preview-checkbox">';
                preview += '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                if (!empty(field.options)) {
                    for (var i = 0; i < field.options.length; i++) {
                        var option = field.options[i];
                        preview += '<div class="lr-kinsa-preview-checkbox-item">';
                        preview += '<input type="checkbox" class="lr-kinsa-preview-checkbox-input" disabled>';
                        preview += '<label>' + option.label + '</label>';
                        preview += '</div>';
                    }
                }
                preview += '</div>';
                break;
            case 'file':
                preview = '<label class="lr-kinsa-preview-label">' + field.label + (!empty(field.required) ? ' <span class="required">*</span>' : '') + '</label>';
                preview += '<input type="file" class="lr-kinsa-preview-input" disabled>';
                if (!empty(field.allowed_types)) {
                    preview += '<p class="description">Tipos permitidos: ' + field.allowed_types + '</p>';
                }
                break;
            case 'html':
                preview = '<div class="lr-kinsa-preview-html">';
                if (isset(field.html_content)) {
                    preview += field.html_content;
                } else {
                    preview += '<p>Contenido HTML personalizado</p>';
                }
                preview += '</div>';
                break;
            case 'heading':
                var headingLevel = isset(field.heading_level) ? field.heading_level : 'h3';
                preview = '<' + headingLevel + ' class="lr-kinsa-preview-heading">' + field.label + '</' + headingLevel + '>';
                break;
            case 'paragraph':
                preview = '<p class="lr-kinsa-preview-paragraph">' + field.label + '</p>';
                break;
            case 'divider':
                preview = '<div class="lr-kinsa-preview-divider"></div>';
                break;
            case 'spacer':
                var height = isset(field.height) ? field.height : '20';
                preview = '<div class="lr-kinsa-preview-spacer" style="height: ' + height + 'px;"></div>';
                break;
            case 'correlative':
                var prefix = isset(field.correlative_prefix) ? field.correlative_prefix : 'FORM-';
                preview = '<div class="lr-kinsa-preview-correlative">' + prefix + '00001</div>';
                break;
            case 'hidden':
                preview = '<div class="lr-kinsa-preview-hidden"><em>Campo oculto: ' + field.label + '</em></div>';
                break;
            case 'recaptcha':
                preview = '<div class="lr-kinsa-preview-recaptcha">reCAPTCHA</div>';
                break;
            case 'submit':
                preview = '<button type="button" class="lr-kinsa-preview-submit">' + field.label + '</button>';
                break;
        }
        
        return preview;
    }
    
    // Helper functions for JavaScript
    function isset(variable) {
        return typeof variable !== 'undefined' && variable !== null;
    }
    
    function empty(variable) {
        return !isset(variable) || variable === '' || variable === false || 
               (Array.isArray(variable) && variable.length === 0) || 
               (typeof variable === 'object' && Object.keys(variable).length === 0);
    }
    
    // Load field settings
    function loadFieldSettings(fieldId, fieldType) {
        if (formData.fields[fieldId]) {
            var field = formData.fields[fieldId];
            
            // Reset all settings
            $('.lr-kinsa-setting-group').hide();
            
            // Show common settings
            $('.lr-kinsa-setting-label').show();
            $('.lr-kinsa-setting-name').show();
            $('.lr-kinsa-setting-css-class').show();
            
            // Set common values
            $('#lr-kinsa-field-label').val(field.label);
            $('#lr-kinsa-field-name').val(field.name);
            $('#lr-kinsa-field-css-class').val(field.css_class || '');
            
            // Show and set type-specific settings
            switch (fieldType) {
                case 'text':
                case 'email':
                case 'tel':
                case 'date':
                    $('.lr-kinsa-setting-placeholder').show();
                    $('.lr-kinsa-setting-default').show();
                    $('.lr-kinsa-setting-required').show();
                    $('.lr-kinsa-setting-description').show();
                    
                    $('#lr-kinsa-field-placeholder').val(field.placeholder || '');
                    $('#lr-kinsa-field-default').val(field.default || '');
                    $('#lr-kinsa-field-required').prop('checked', field.required || false);
                    $('#lr-kinsa-field-description').val(field.description || '');
                    break;
                case 'textarea':
                    $('.lr-kinsa-setting-placeholder').show();
                    $('.lr-kinsa-setting-default').show();
                    $('.lr-kinsa-setting-required').show();
                    $('.lr-kinsa-setting-description').show();
                    
                    $('#lr-kinsa-field-placeholder').val(field.placeholder || '');
                    $('#lr-kinsa-field-default').val(field.default || '');
                    $('#lr-kinsa-field-required').prop('checked', field.required || false);
                    $('#lr-kinsa-field-description').val(field.description || '');
                    break;
                case 'number':
                    $('.lr-kinsa-setting-placeholder').show();
                    $('.lr-kinsa-setting-default').show();
                    $('.lr-kinsa-setting-required').show();
                    $('.lr-kinsa-setting-description').show();
                    $('.lr-kinsa-setting-min').show();
                    $('.lr-kinsa-setting-max').show();
                    $('.lr-kinsa-setting-step').show();
                    
                    $('#lr-kinsa-field-placeholder').val(field.placeholder || '');
                    $('#lr-kinsa-field-default').val(field.default || '');
                    $('#lr-kinsa-field-required').prop('checked', field.required || false);
                    $('#lr-kinsa-field-description').val(field.description || '');
                    $('#lr-kinsa-field-min').val(field.min || '');
                    $('#lr-kinsa-field-max').val(field.max || '');
                    $('#lr-kinsa-field-step').val(field.step || '1');
                    break;
                case 'select':
                case 'radio':
                case 'checkbox':
                    $('.lr-kinsa-setting-required').show();
                    $('.lr-kinsa-setting-description').show();
                    $('.lr-kinsa-setting-options').show();
                    
                    $('#lr-kinsa-field-required').prop('checked', field.required || false);
                    $('#lr-kinsa-field-description').val(field.description || '');
                    
                    // Load options
                    $('.lr-kinsa-options-list').empty();
                    if (field.options && field.options.length) {
                        field.options.forEach(function(option) {
                            var optionTemplate = $('.lr-kinsa-option-template').html();
                            var optionItem = $(optionTemplate);
                            optionItem.find('.lr-kinsa-option-value').val(option.value || '');
                            optionItem.find('.lr-kinsa-option-label').val(option.label || '');
                            $('.lr-kinsa-options-list').append(optionItem);
                        });
                    } else {
                        // Add default options
                        $('.lr-kinsa-add-option').click();
                    }
                    break;
                case 'file':
                    $('.lr-kinsa-setting-required').show();
                    $('.lr-kinsa-setting-description').show();
                    $('.lr-kinsa-setting-allowed-types').show();
                    $('.lr-kinsa-setting-max-size').show();
                    
                    $('#lr-kinsa-field-required').prop('checked', field.required || false);
                    $('#lr-kinsa-field-description').val(field.description || '');
                    $('#lr-kinsa-field-allowed-types').val(field.allowed_types || 'jpg,png,pdf');
                    $('#lr-kinsa-field-max-size').val(field.max_size || '5');
                    break;
                case 'html':
                    $('.lr-kinsa-setting-html').show();
                    
                    $('#lr-kinsa-field-html').val(field.html_content || '');
                    break;
                case 'heading':
                    $('.lr-kinsa-setting-heading-level').show();
                    
                    $('#lr-kinsa-field-heading-level').val(field.heading_level || 'h3');
                    break;
                case 'spacer':
                    $('.lr-kinsa-setting-height').show();
                    
                    $('#lr-kinsa-field-height').val(field.height || '20');
                    break;
                case 'correlative':
                    $('.lr-kinsa-setting-correlative-prefix').show();
                    
                    $('#lr-kinsa-field-correlative-prefix').val(field.correlative_prefix || 'FORM-');
                    break;
                case 'hidden':
                    $('.lr-kinsa-setting-default').show();
                    
                    $('#lr-kinsa-field-default').val(field.default || '');
                    break;
            }
        }
    }
    
    // Update options
    function updateOptions() {
        if (selectedField && formData.fields[selectedField]) {
            var options = [];
            
            $('.lr-kinsa-option-item').each(function() {
                var value = $(this).find('.lr-kinsa-option-value').val();
                var label = $(this).find('.lr-kinsa-option-label').val();
                
                options.push({
                    value: value,
                    label: label || value
                });
            });
            
            // Update field data
            formData.fields[selectedField].options = options;
            
            // Update field preview
            var fieldItem = $('.lr-kinsa-field-item[data-field-id="' + selectedField + '"]');
            fieldItem.find('.lr-kinsa-field-preview').html(renderFieldPreview(formData.fields[selectedField]));
            
            // Update form data
            updateFormData();
        }
    }
    
// Función para actualizar los datos del formulario
function updateFormData() {
    // Mantener la estructura original pero actualizar los datos
    var updatedFormData = {
        layout: {},
        fields: formData.fields || {} // Preservar los campos existentes
    };
    
    // Obtener layouts
    $('.lr-kinsa-layout-item').each(function() {
        var layoutId = $(this).data('layout-id');
        var layoutType = $(this).hasClass('lr-kinsa-one-column') ? 'one_column' : 'two_columns';
        
        // Crear objeto de layout
        updatedFormData.layout[layoutId] = {
            id: layoutId,
            type: layoutType,
            columns: {}
        };
        
        // Obtener campos en cada columna
        $(this).find('.lr-kinsa-column').each(function() {
            var columnNum = $(this).data('column');
            var fields = [];
            
            // Obtener campos en esta columna
            $(this).find('.lr-kinsa-field-item').each(function() {
                var fieldId = $(this).data('field-id');
                fields.push(fieldId);
            });
            
            // Agregar campos a la columna
            updatedFormData.layout[layoutId].columns[columnNum] = fields;
        });
    });
    
    // Actualizar campo oculto con los datos del formulario
    $('#lr-kinsa-form-data').val(JSON.stringify(updatedFormData));
    
    // Actualizar la variable global formData
    formData = updatedFormData;
    
    console.log("Datos actualizados:", updatedFormData); // Para depuración
    return updatedFormData;
}

// Guardar formulario cuando se hace clic en el botón
$(document).on('click', '#lr-kinsa-save-form-button', function(e) {
    e.preventDefault();
    console.log("Botón de guardar clickeado"); // Para depuración
    
    // Actualizar los datos del formulario
    updateFormData();
    
    // Obtener los datos del formulario
    var formDataJson = $('#lr-kinsa-form-data').val();
    var formId = <?php echo $post->ID; ?>;
    var nonce = '<?php echo wp_create_nonce('lr_kinsa_save_form'); ?>';
    
    console.log("Datos a enviar:", formDataJson); // Para depuración
    
    // Mostrar indicador de carga
    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Guardando...');
    
    // Enviar datos mediante AJAX
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'lr_kinsa_save_form_ajax',
            form_id: formId,
            lr_kinsa_form_data: formDataJson,
            lr_kinsa_form_nonce: nonce
        },
        success: function(response) {
            console.log("Respuesta:", response); // Para depuración
            if (response.success) {
                // Mostrar mensaje de éxito
                alert('Formulario guardado correctamente.');
            } else {
                // Mostrar mensaje de error
                alert('Error al guardar el formulario: ' + (response.data ? response.data.message : 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX:", xhr.responseText); // Para depuración
            alert('Error de conexión al guardar el formulario: ' + error);
        },
        complete: function() {
            // Restaurar el botón
            $('#lr-kinsa-save-form-button').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar Formulario');
        }
    });
    
    return false;
});
    
    // Generate preview HTML
    function generatePreviewHTML() {
        var formTitle = $('input[name="lr_kinsa_form_settings[title]"]').val();
        var submitText = $('input[name="lr_kinsa_form_settings[submit_text]"]').val();
        
        // Start building HTML
        var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vista Previa del Formulario</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    line-height: 1.5;
                    color: #333;
                    background: #f5f5f5;
                    padding: 20px;
                    margin: 0;
                }
                
                .lr-kinsa-form-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: #fff;
                    padding: 25px;
                    border-radius: 5px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }
                
                .lr-kinsa-form-title {
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .lr-kinsa-form-title h3 {
                    margin: 0;
                    font-size: 24px;
                    color: #333;
                }
                
                .lr-kinsa-form {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                
                .lr-kinsa-layout-content {
                    display: flex;
                    gap: 20px;
                }
                
                .lr-kinsa-one-column .lr-kinsa-layout-content {
                    flex-direction: column;
                }
                
                .lr-kinsa-two-columns .lr-kinsa-layout-content {
                    flex-direction: row;
                }
                
                .lr-kinsa-column {
                    flex: 1;
                }
                
                .lr-kinsa-field {
                    margin-bottom: 20px;
                }
                
                .lr-kinsa-field label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 500;
                }
                
                .lr-kinsa-field input[type="text"],
                .lr-kinsa-field input[type="email"],
                .lr-kinsa-field input[type="tel"],
                .lr-kinsa-field input[type="number"],
                .lr-kinsa-field input[type="date"],
                .lr-kinsa-field select,
                .lr-kinsa-field textarea {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                
                .lr-kinsa-field textarea {
                    min-height: 120px;
                }
                
                .lr-kinsa-radio-group,
                .lr-kinsa-checkbox-group {
                    margin-top: 5px;
                }
                
                .lr-kinsa-radio-item,
                .lr-kinsa-checkbox-item {
                    display: flex;
                    align-items: center;
                    margin-bottom: 8px;
                }
                
                .lr-kinsa-radio-item input,
                .lr-kinsa-checkbox-item input {
                    margin-right: 8px;
                }
                
                .lr-kinsa-html-content {
                    margin-bottom: 20px;
                }
                
                .lr-kinsa-divider {
                    border-top: 1px solid #ddd;
                    margin: 20px 0;
                }
                
                .lr-kinsa-spacer {
                    margin-bottom: 20px;
                }
                
                .lr-kinsa-correlative {
                    padding: 10px;
                    background: #f0f0f1;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-weight: 600;
                    margin-bottom: 20px;
                }
                
                .lr-kinsa-submit-button {
                    background: #2271b1;
                    color: #fff;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                    align-self: center;
                }
                
                .required {
                    color: #dc3545;
                }
                
                .lr-kinsa-form-footer {
                    text-align: center;
                    margin-top: 20px;
                    padding: 10px;
                    color: #666;
                    font-size: 12px;
                }
                
                .lr-kinsa-form-footer a {
                    color: #2271b1;
                    text-decoration: none;
                }
                
                @media (max-width: 768px) {
                    .lr-kinsa-two-columns .lr-kinsa-layout-content {
                        flex-direction: column;
                    }
                }
            </style>
        </head>
        <body>
            <div class="lr-kinsa-form-container">
        `;
        
        // Add form title
        if (formTitle) {
            html += `
                <div class="lr-kinsa-form-title">
                    <h3>${formTitle}</h3>
                </div>
            `;
        }
        
        // Start form
        html += `<form class="lr-kinsa-form">`;
        
        // Add layouts and fields
        var layoutIds = Object.keys(formData.layout);
        layoutIds.forEach(function(layoutId) {
            var layout = formData.layout[layoutId];
            
            if (layout.type === 'one_column') {
                html += `<div class="lr-kinsa-layout lr-kinsa-one-column">`;
                html += `<div class="lr-kinsa-layout-content">`;
                html += `<div class="lr-kinsa-column">`;
                
                // Add fields in column 1
                if (layout.columns[1] && layout.columns[1].length) {
                    layout.columns[1].forEach(function(fieldId) {
                        if (formData.fields[fieldId]) {
                            html += renderFieldHTML(formData.fields[fieldId]);
                        }
                    });
                }
                
                html += `</div>`;
                html += `</div>`;
                html += `</div>`;
            } else if (layout.type === 'two_columns') {
                html += `<div class="lr-kinsa-layout lr-kinsa-two-columns">`;
                html += `<div class="lr-kinsa-layout-content">`;
                
                // Column 1
                html += `<div class="lr-kinsa-column">`;
                if (layout.columns[1] && layout.columns[1].length) {
                    layout.columns[1].forEach(function(fieldId) {
                        if (formData.fields[fieldId]) {
                            html += renderFieldHTML(formData.fields[fieldId]);
                        }
                    });
                }
                html += `</div>`;
                
                // Column 2
                html += `<div class="lr-kinsa-column">`;
                if (layout.columns[2] && layout.columns[2].length) {
                    layout.columns[2].forEach(function(fieldId) {
                        if (formData.fields[fieldId]) {
                            html += renderFieldHTML(formData.fields[fieldId]);
                        }
                    });
                }
                html += `</div>`;
                
                html += `</div>`;
                html += `</div>`;
            }
        });
        
        // Add submit button
        html += `
            <button type="submit" class="lr-kinsa-submit-button">${submitText || 'Enviar'}</button>
        </form>
        <div class="lr-kinsa-form-footer">
            Plugin creado por <a href="https://nebu-lab.com/" target="_blank">Nebu-lab</a>
        </div>
        </div>
        </body>
        </html>
        `;
        
        return html;
    }
    
    // Render field HTML for preview
    function renderFieldHTML(field) {
        var html = '';
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
            case 'date':
                html += '<div class="lr-kinsa-field">';
                html += '<label for="' + field.name + '">' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<input type="' + field.type + '" id="' + field.name + '" name="' + field.name + '" placeholder="' + (field.placeholder || '') + '"' + (field.required ? ' required' : '') + '>';
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'textarea':
                html += '<div class="lr-kinsa-field">';
                html += '<label for="' + field.name + '">' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<textarea id="' + field.name + '" name="' + field.name + '" placeholder="' + (field.placeholder || '') + '"' + (field.required ? ' required' : '') + '></textarea>';
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'select':
                html += '<div class="lr-kinsa-field">';
                html += '<label for="' + field.name + '">' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<select id="' + field.name + '" name="' + field.name + '"' + (field.required ? ' required' : '') + '>';
                html += '<option value="">' + (field.placeholder || 'Seleccionar...') + '</option>';
                if (field.options && field.options.length) {
                    field.options.forEach(function(option) {
                        html += '<option value="' + option.value + '">' + option.label + '</option>';
                    });
                }
                html += '</select>';
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'radio':
                html += '<div class="lr-kinsa-field">';
                html += '<label>' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<div class="lr-kinsa-radio-group">';
                if (field.options && field.options.length) {
                    field.options.forEach(function(option, index) {
                        html += '<div class="lr-kinsa-radio-item">';
                        html += '<input type="radio" id="' + field.name + '_' + index + '" name="' + field.name + '" value="' + option.value + '"' + (field.required ? ' required' : '') + '>';
                        html += '<label for="' + field.name + '_' + index + '">' + option.label + '</label>';
                        html += '</div>';
                    });
                }
                html += '</div>';
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'checkbox':
                html += '<div class="lr-kinsa-field">';
                html += '<label>' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<div class="lr-kinsa-checkbox-group">';
                if (field.options && field.options.length) {
                    field.options.forEach(function(option, index) {
                        html += '<div class="lr-kinsa-checkbox-item">';
                        html += '<input type="checkbox" id="' + field.name + '_' + index + '" name="' + field.name + '[]" value="' + option.value + '"' + (field.required ? ' required' : '') + '>';
                        html += '<label for="' + field.name + '_' + index + '">' + option.label + '</label>';
                        html += '</div>';
                    });
                }
                html += '</div>';
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'file':
                html += '<div class="lr-kinsa-field">';
                html += '<label for="' + field.name + '">' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                html += '<input type="file" id="' + field.name + '" name="' + field.name + '"' + (field.required ? ' required' : '') + '>';
                if (field.allowed_types) {
                    html += '<p class="description">Tipos permitidos: ' + field.allowed_types + '</p>';
                }
                if (field.description) {
                    html += '<p class="description">' + field.description + '</p>';
                }
                html += '</div>';
                break;
            case 'html':
                html += '<div class="lr-kinsa-html-content">' + (field.html_content || '') + '</div>';
                break;
            case 'heading':
                var headingLevel = field.heading_level || 'h3';
                html += '<' + headingLevel + ' class="lr-kinsa-heading">' + field.label + '</' + headingLevel + '>';
                break;
            case 'paragraph':
                html += '<p class="lr-kinsa-paragraph">' + field.label + '</p>';
                break;
            case 'divider':
                html += '<div class="lr-kinsa-divider"></div>';
                break;
            case 'spacer':
                html += '<div class="lr-kinsa-spacer" style="height: ' + (field.height || '20') + 'px;"></div>';
                break;
            case 'correlative':
                html += '<div class="lr-kinsa-correlative">' + (field.correlative_prefix || 'FORM-') + '00001</div>';
                break;
            case 'hidden':
                html += '<input type="hidden" id="' + field.name + '" name="' + field.name + '" value="' + (field.default || '') + '">';
                break;
            case 'recaptcha':
                html += '<div class="lr-kinsa-recaptcha">reCAPTCHA</div>';
                break;
        }
        
        return html;
    }
});
	
// Guardar formulario cuando se hace clic en el botón
$('#lr-kinsa-save-form-button').on('click', function() {
    // Actualizar formData con los datos actuales
    updateFormData();
    
    // Obtener los datos del formulario actualizados
    var formDataJson = $('#lr-kinsa-form-data').val();
    var formId = <?php echo $post->ID; ?>;
    var nonce = '<?php echo wp_create_nonce('lr_kinsa_save_form'); ?>';
    
    // Mostrar indicador de carga
    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Guardando...');
    
    // Enviar datos mediante AJAX
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'lr_kinsa_save_form_ajax',
            form_id: formId,
            lr_kinsa_form_data: formDataJson,
            lr_kinsa_form_nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                // Mostrar mensaje de éxito
                alert('Formulario guardado correctamente.');
            } else {
                // Mostrar mensaje de error
                alert('Error al guardar el formulario: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error de conexión al guardar el formulario: ' + error);
            console.error(xhr.responseText);
        },
        complete: function() {
            // Restaurar el botón
            $('#lr-kinsa-save-form-button').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar Formulario');
        }
    });
});
</script>
    <?php
}

// Shortcode meta box
function lr_kinsa_form_shortcode_callback($post) {
    ?>
    <p>Utiliza este shortcode para mostrar el formulario en cualquier página o entrada:</p>
    <p><code>[lr_kinsa_form id="<?php echo $post->ID; ?>"]</code></p>
    <button type="button" class="button" onclick="copyToClipboard('[lr_kinsa_form id=&quot;<?php echo $post->ID; ?>&quot;]')">Copiar Shortcode</button>
    <script>
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Shortcode copiado al portapapeles');
        }
    </script>
    <?php
}

// Helper function to render field preview
function lr_kinsa_render_field_preview($field) {
    $preview = '';
    
    switch ($field['type']) {
        case 'text':
        case 'email':
        case 'tel':
        case 'number':
        case 'date':
            $preview = '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $preview .= '<input type="' . $field['type'] . '" class="lr-kinsa-preview-input" placeholder="' . (isset($field['placeholder']) ? $field['placeholder'] : '') . '" disabled>';
            break;
        case 'textarea':
            $preview = '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $preview .= '<textarea class="lr-kinsa-preview-textarea" placeholder="' . (isset($field['placeholder']) ? $field['placeholder'] : '') . '" disabled></textarea>';
            break;
        case 'select':
            $preview = '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $preview .= '<select class="lr-kinsa-preview-select" disabled>';
            $preview .= '<option value="">' . (isset($field['placeholder']) ? $field['placeholder'] : 'Seleccionar...') . '</option>';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $preview .= '<option value="' . $option['value'] . '">' . $option['label'] . '</option>';
                }
            }
            $preview .= '</select>';
            break;
        case 'radio':
            $preview = '<div class="lr-kinsa-preview-radio">';
            $preview .= '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $preview .= '<div class="lr-kinsa-preview-radio-item">';
                    $preview .= '<input type="radio" class="lr-kinsa-preview-radio-input" disabled>';
                    $preview .= '<label>' . $option['label'] . '</label>';
                    $preview .= '</div>';
                }
            }
            $preview .= '</div>';
            break;
        case 'checkbox':
            $preview = '<div class="lr-kinsa-preview-checkbox">';
            $preview .= '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $preview .= '<div class="lr-kinsa-preview-checkbox-item">';
                    $preview .= '<input type="checkbox" class="lr-kinsa-preview-checkbox-input" disabled>';
                    $preview .= '<label>' . $option['label'] . '</label>';
                    $preview .= '</div>';
                }
            }
            $preview .= '</div>';
            break;
        case 'file':
            $preview = '<label class="lr-kinsa-preview-label">' . $field['label'] . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $preview .= '<input type="file" class="lr-kinsa-preview-input" disabled>';
            if (!empty($field['allowed_types'])) {
                $preview .= '<p class="description">Tipos permitidos: ' . $field['allowed_types'] . '</p>';
            }
            break;
        case 'html':
            $preview = '<div class="lr-kinsa-preview-html">';
            if (isset($field['html_content'])) {
                $preview .= $field['html_content'];
            } else {
                $preview .= '<p>Contenido HTML personalizado</p>';
            }
            $preview .= '</div>';
            break;
        case 'heading':
            $preview = '<' . (isset($field['heading_level']) ? $field['heading_level'] : 'h3') . ' class="lr-kinsa-preview-heading">' . $field['label'] . '</' . (isset($field['heading_level']) ? $field['heading_level'] : 'h3') . '>';
            break;
        case 'paragraph':
            $preview = '<p class="lr-kinsa-preview-paragraph">' . $field['label'] . '</p>';
            break;
        case 'divider':
            $preview = '<div class="lr-kinsa-preview-divider"></div>';
            break;
        case 'spacer':
            $preview = '<div class="lr-kinsa-preview-spacer" style="height: ' . (isset($field['height']) ? $field['height'] : '20') . 'px;"></div>';
            break;
        case 'correlative':
            $preview = '<div class="lr-kinsa-preview-correlative">' . (isset($field['correlative_prefix']) ? $field['correlative_prefix'] : 'FORM-') . '00001</div>';
            break;
        case 'hidden':
            $preview = '<div class="lr-kinsa-preview-hidden"><em>Campo oculto: ' . $field['label'] . '</em></div>';
            break;
        case 'recaptcha':
            $preview = '<div class="lr-kinsa-preview-recaptcha">reCAPTCHA</div>';
            break;
        case 'submit':
            $preview = '<button type="button" class="lr-kinsa-preview-submit">' . $field['label'] . '</button>';
            break;
    }
    
    return $preview;
}

function lr_kinsa_save_form_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['lr_kinsa_form_nonce'])) {
        return;
    }
    
    // Verify the nonce
    if (!wp_verify_nonce($_POST['lr_kinsa_form_nonce'], 'lr_kinsa_save_form')) {
        return;
    }
    
    // If this is an autosave, we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save form data
    if (isset($_POST['lr_kinsa_form_data'])) {
        // Asegurarse de que los datos JSON se decodifican correctamente
        $form_data_json = wp_unslash($_POST['lr_kinsa_form_data']);
        $form_data = json_decode($form_data_json, true);
        
        if ($form_data !== null) {
            update_post_meta($post_id, '_lr_kinsa_form_data', $form_data);
        }
    }
    
    // Save form settings
    if (isset($_POST['lr_kinsa_form_settings'])) {
        update_post_meta($post_id, '_lr_kinsa_form_settings', $_POST['lr_kinsa_form_settings']);
    }
}
add_action('save_post_lr_kinsa_form', 'lr_kinsa_save_form_data');

// Register shortcode
function lr_kinsa_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'lr_kinsa_form');
    
    $form_id = intval($atts['id']);
    
    if ($form_id <= 0) {
        return '<p>ID de formulario inválido.</p>';
    }
    
    $form_data = get_post_meta($form_id, '_lr_kinsa_form_data', true);
    $form_settings = get_post_meta($form_id, '_lr_kinsa_form_settings', true);
    
    if (empty($form_data) || empty($form_data['layout'])) {
        return '<p>El formulario está vacío o no existe.</p>';
    }
    
    if (empty($form_settings)) {
        $form_settings = array(
            'title' => '',
            'email_to' => get_option('admin_email'),
            'success_message' => 'Gracias por tu mensaje. Ha sido enviado correctamente.',
            'submit_text' => 'Enviar',
            'form_class' => '',
            'form_style' => 'default',
            'correlative_prefix' => 'FORM-',
            'correlative_start' => '00001'
        );
    }
    
    // Generate correlative number
    $correlative_count = get_post_meta($form_id, '_lr_kinsa_correlative_count', true);
    if (empty($correlative_count)) {
        $correlative_count = intval($form_settings['correlative_start']);
    } else {
        $correlative_count++;
    }
    update_post_meta($form_id, '_lr_kinsa_correlative_count', $correlative_count);
    
    $correlative_number = $form_settings['correlative_prefix'] . str_pad($correlative_count, 5, '0', STR_PAD_LEFT);
    
    // Enqueue styles and scripts
    wp_enqueue_style('lr-kinsa-form-style');
    wp_enqueue_script('lr-kinsa-form-script');
    
    ob_start();
    ?>
    <div class="lr-kinsa-form-container <?php echo esc_attr($form_settings['form_class']); ?> lr-kinsa-form-style-<?php echo esc_attr($form_settings['form_style']); ?>">
        <?php if (!empty($form_settings['title'])): ?>
            <div class="lr-kinsa-form-title">
                <h3><?php echo esc_html($form_settings['title']); ?></h3>
            </div>
        <?php endif; ?>
        
        <form class="lr-kinsa-form" id="lr-kinsa-form-<?php echo $form_id; ?>" data-form-id="<?php echo $form_id; ?>">
            <?php
            // Add layouts and fields
            $layout_ids = array_keys($form_data['layout']);
            foreach ($layout_ids as $layout_id) {
                $layout = $form_data['layout'][$layout_id];
                
                if ($layout['type'] === 'one_column') {
                    echo '<div class="lr-kinsa-layout lr-kinsa-one-column">';
                    echo '<div class="lr-kinsa-layout-content">';
                    echo '<div class="lr-kinsa-column">';
                    
                    // Add fields in column 1
                    if (!empty($layout['columns'][1])) {
                        foreach ($layout['columns'][1] as $field_id) {
                            if (!empty($form_data['fields'][$field_id])) {
                                echo lr_kinsa_render_field($form_data['fields'][$field_id], $correlative_number);
                            }
                        }
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                } elseif ($layout['type'] === 'two_columns') {
                    echo '<div class="lr-kinsa-layout lr-kinsa-two-columns">';
                    echo '<div class="lr-kinsa-layout-content">';
                    
                    // Column 1
                    echo '<div class="lr-kinsa-column">';
                    if (!empty($layout['columns'][1])) {
                        foreach ($layout['columns'][1] as $field_id) {
                            if (!empty($form_data['fields'][$field_id])) {
                                echo lr_kinsa_render_field($form_data['fields'][$field_id], $correlative_number);
                            }
                        }
                    }
                    echo '</div>';
                    
                    // Column 2
                    echo '<div class="lr-kinsa-column">';
                    if (!empty($layout['columns'][2])) {
                        foreach ($layout['columns'][2] as $field_id) {
                            if (!empty($form_data['fields'][$field_id])) {
                                echo lr_kinsa_render_field($form_data['fields'][$field_id], $correlative_number);
                            }
                        }
                    }
                    echo '</div>';
                    
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
            
            <div class="lr-kinsa-form-submit">
                <button type="submit" class="lr-kinsa-submit-button"><?php echo esc_html($form_settings['submit_text']); ?></button>
            </div>
            
            <div class="lr-kinsa-form-message"></div>
            <?php wp_nonce_field('lr_kinsa_submit_form', 'lr_kinsa_nonce'); ?>
            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
            <input type="hidden" name="correlative_number" value="<?php echo $correlative_number; ?>">
        </form>
        <div class="lr-kinsa-form-footer">
            Plugin creado por <a href="https://nebu-lab.com/" target="_blank">Nebu-lab</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('lr_kinsa_form', 'lr_kinsa_form_shortcode');

// Render field for frontend
function lr_kinsa_render_field($field, $correlative_number) {
    $html = '';
    
    switch ($field['type']) {
        case 'text':
        case 'email':
        case 'tel':
        case 'number':
        case 'date':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" placeholder="' . esc_attr(isset($field['placeholder']) ? $field['placeholder'] : '') . '" value="' . esc_attr(isset($field['default']) ? $field['default'] : '') . '"' . (!empty($field['required']) ? ' required' : '') . ' class="' . esc_attr(isset($field['css_class']) ? $field['css_class'] : '') . '">';
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'textarea':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<textarea id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" placeholder="' . esc_attr(isset($field['placeholder']) ? $field['placeholder'] : '') . '"' . (!empty($field['required']) ? ' required' : '') . ' class="' . esc_attr(isset($field['css_class']) ? $field['css_class'] : '') . '">' . esc_textarea(isset($field['default']) ? $field['default'] : '') . '</textarea>';
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'select':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<select id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '"' . (!empty($field['required']) ? ' required' : '') . ' class="' . esc_attr(isset($field['css_class']) ? $field['css_class'] : '') . '">';
            $html .= '<option value="">' . esc_html(isset($field['placeholder']) ? $field['placeholder'] : 'Seleccionar...') . '</option>';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $html .= '<option value="' . esc_attr($option['value']) . '"' . (isset($field['default']) && $field['default'] === $option['value'] ? ' selected' : '') . '>' . esc_html($option['label']) . '</option>';
                }
            }
            $html .= '</select>';
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'radio':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label>' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<div class="lr-kinsa-radio-group' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $index => $option) {
                    $html .= '<div class="lr-kinsa-radio-item">';
                    $html .= '<input type="radio" id="' . esc_attr($field['name'] . '_' . $index) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($option['value']) . '"' . (isset($field['default']) && $field['default'] === $option['value'] ? ' checked' : '') . (!empty($field['required']) ? ' required' : '') . '>';
                    $html .= '<label for="' . esc_attr($field['name'] . '_' . $index) . '">' . esc_html($option['label']) . '</label>';
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'checkbox':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label>' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<div class="lr-kinsa-checkbox-group' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">';
            if (!empty($field['options'])) {
                foreach ($field['options'] as $index => $option) {
                    $html .= '<div class="lr-kinsa-checkbox-item">';
                    $html .= '<input type="checkbox" id="' . esc_attr($field['name'] . '_' . $index) . '" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($option['value']) . '"' . (isset($field['default']) && (is_array($field['default']) && in_array($option['value'], $field['default'])) ? ' checked' : '') . (!empty($field['required']) ? ' required' : '') . '>';
                    $html .= '<label for="' . esc_attr($field['name'] . '_' . $index) . '">' . esc_html($option['label']) . '</label>';
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'file':
            $html .= '<div class="lr-kinsa-field">';
            $html .= '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';
            $html .= '<input type="file" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '"' . (!empty($field['required']) ? ' required' : '') . ' class="' . esc_attr(isset($field['css_class']) ? $field['css_class'] : '') . '"' . (!empty($field['allowed_types']) ? ' accept="' . esc_attr(str_replace(',', ',', $field['allowed_types'])) . '"' : '') . '>';
            if (!empty($field['allowed_types'])) {
                $html .= '<p class="description">Tipos permitidos: ' . esc_html($field['allowed_types']) . '</p>';
            }
            if (!empty($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            $html .= '</div>';
            break;
        case 'html':
            $html .= '<div class="lr-kinsa-html-content' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">' . wp_kses_post(isset($field['html_content']) ? $field['html_content'] : '') . '</div>';
            break;
        case 'heading':
            $heading_level = isset($field['heading_level']) ? $field['heading_level'] : 'h3';
            $html .= '<' . $heading_level . ' class="lr-kinsa-heading' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">' . esc_html($field['label']) . '</' . $heading_level . '>';
            break;
        case 'paragraph':
            $html .= '<p class="lr-kinsa-paragraph' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">' . esc_html($field['label']) . '</p>';
            break;
        case 'divider':
            $html .= '<div class="lr-kinsa-divider' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '"></div>';
            break;
        case 'spacer':
            $html .= '<div class="lr-kinsa-spacer' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '" style="height: ' . esc_attr(isset($field['height']) ? $field['height'] : '20') . 'px;"></div>';
            break;
        case 'correlative':
            $html .= '<div class="lr-kinsa-correlative' . (!empty($field['css_class']) ? ' ' . esc_attr($field['css_class']) : '') . '">' . esc_html($correlative_number) . '</div>';
            break;
        case 'hidden':
            $html .= '<input type="hidden" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr(isset($field['default']) ? $field['default'] : '') . '">';
            break;
        case 'recaptcha':
            if (isset($form_settings['enable_recaptcha']) && $form_settings['enable_recaptcha']) {
                $html .= '<div class="g-recaptcha" data-sitekey="' . esc_attr(get_option('lr_kinsa_recaptcha_site_key', '')) . '"></div>';
            }
            break;
    }
    
    return $html;
}

// Register and enqueue styles and scripts
function lr_kinsa_enqueue_scripts() {
    // Frontend styles and scripts
    wp_register_style('lr-kinsa-form-style', false);
    wp_add_inline_style('lr-kinsa-form-style', lr_kinsa_frontend_css());
    
    wp_register_script('lr-kinsa-form-script', false);
    wp_enqueue_script('lr-kinsa-form-script', array('jquery'), '', true);
    wp_add_inline_script('lr-kinsa-form-script', lr_kinsa_frontend_js());
    
    // Add ajax url
    wp_localize_script('lr-kinsa-form-script', 'lr_kinsa_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lr_kinsa_submit_form'),
    ));
}
add_action('wp_enqueue_scripts', 'lr_kinsa_enqueue_scripts');

// Frontend CSS
function lr_kinsa_frontend_css() {
    ob_start();
    ?>
    /* Form Styles */
    .lr-kinsa-form-container {
        max-width: 800px;
        margin: 0 auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .lr-kinsa-form-title {
        margin-bottom: 20px;
        text-align: center;
    }
    
    .lr-kinsa-form-title h3 {
        margin: 0;
        font-size: 24px;
        color: #333;
    }
    
    .lr-kinsa-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .lr-kinsa-layout-content {
        display: flex;
        gap: 20px;
    }
    
    .lr-kinsa-one-column .lr-kinsa-layout-content {
        flex-direction: column;
    }
    
    .lr-kinsa-two-columns .lr-kinsa-layout-content {
        flex-direction: row;
    }
    
    .lr-kinsa-column {
        flex: 1;
    }
    
    .lr-kinsa-field {
        margin-bottom: 20px;
    }
    
    .lr-kinsa-field label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    
    .lr-kinsa-field input[type="text"],
    .lr-kinsa-field input[type="email"],
    .lr-kinsa-field input[type="tel"],
    .lr-kinsa-field input[type="number"],
    .lr-kinsa-field input[type="date"],
    .lr-kinsa-field select,
    .lr-kinsa-field textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        box-sizing: border-box;
    }
    
    .lr-kinsa-field textarea {
        min-height: 120px;
    }
    
    .lr-kinsa-radio-group,
    .lr-kinsa-checkbox-group {
        margin-top: 5px;
    }
    
    .lr-kinsa-radio-item,
    .lr-kinsa-checkbox-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .lr-kinsa-radio-item input,
    .lr-kinsa-checkbox-item input {
        margin-right: 8px;
    }
    
    .lr-kinsa-html-content {
        margin-bottom: 20px;
    }
    
    .lr-kinsa-heading {
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    .lr-kinsa-paragraph {
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    .lr-kinsa-divider {
        border-top: 1px solid #ddd;
        margin: 20px 0;
    }
    
    .lr-kinsa-spacer {
        margin-bottom: 20px;
    }
    
    .lr-kinsa-correlative {
        padding: 10px;
        background: #f0f0f1;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .lr-kinsa-form-submit {
        text-align: center;
        margin-top: 20px;
    }
    
    .lr-kinsa-submit-button {
        background: #2271b1;
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .lr-kinsa-submit-button:hover {
        background: #135e96;
    }
    
    .lr-kinsa-form-message {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
        display: none;
    }
    
    .lr-kinsa-form-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .lr-kinsa-form-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .lr-kinsa-form-footer {
        text-align: center;
        margin-top: 15px;
        font-size: 12px;
        color: #666;
    }
    
    .lr-kinsa-form-footer a {
        color: #2271b1;
        text-decoration: none;
    }
    
    .lr-kinsa-form-footer a:hover {
        text-decoration: underline;
    }
    
    .required {
        color: #dc3545;
    }
    
    .description {
        font-size: 12px;
        color: #777;
        margin-top: 5px;
    }
    
    /* Form Styles */
    .lr-kinsa-form-style-flat .lr-kinsa-form {
        border-radius: 0;
        box-shadow: none;
    }
    
    .lr-kinsa-form-style-flat .lr-kinsa-field input,
    .lr-kinsa-form-style-flat .lr-kinsa-field select,
    .lr-kinsa-form-style-flat .lr-kinsa-field textarea {
        border: none;
        background: #f5f5f5;
        border-radius: 0;
    }
    
    .lr-kinsa-form-style-flat .lr-kinsa-submit-button {
        border-radius: 0;
    }
    
    .lr-kinsa-form-style-material .lr-kinsa-field input,
    .lr-kinsa-form-style-material .lr-kinsa-field select,
    .lr-kinsa-form-style-material .lr-kinsa-field textarea {
        border: none;
        border-bottom: 2px solid #ddd;
        border-radius: 0;
        padding: 10px 0;
        background: transparent;
        transition: border-color 0.3s;
    }
    
    .lr-kinsa-form-style-material .lr-kinsa-field input:focus,
    .lr-kinsa-form-style-material .lr-kinsa-field select:focus,
    .lr-kinsa-form-style-material .lr-kinsa-field textarea:focus {
        border-bottom-color: #2271b1;
        outline: none;
    }
    
    .lr-kinsa-form-style-material .lr-kinsa-submit-button {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .lr-kinsa-form-style-minimal .lr-kinsa-form {
        box-shadow: none;
        padding: 0;
        background: transparent;
    }
    
    .lr-kinsa-form-style-minimal .lr-kinsa-field input,
    .lr-kinsa-form-style-minimal .lr-kinsa-field select,
    .lr-kinsa-form-style-minimal .lr-kinsa-field textarea {
        border: 1px solid #ddd;
        padding: 8px;
        font-size: 14px;
    }
    
    .lr-kinsa-form-style-minimal .lr-kinsa-submit-button {
        background: #333;
        padding: 8px 16px;
        font-size: 14px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .lr-kinsa-two-columns .lr-kinsa-layout-content {
            flex-direction: column;
        }
    }
    <?php
    return ob_get_clean();
}

// Frontend JavaScript
function lr_kinsa_frontend_js() {
    ob_start();
    ?>
    jQuery(document).ready(function($) {
        $('.lr-kinsa-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formId = form.data('form-id');
            const messageContainer = form.find('.lr-kinsa-form-message');
            const submitButton = form.find('.lr-kinsa-submit-button');
            
            // Disable submit button
            submitButton.prop('disabled', true).text('Enviando...');
            
            // Clear previous messages
            messageContainer.removeClass('success error').hide().empty();
            
            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'lr_kinsa_submit_form');
            
            // Send AJAX request
            $.ajax({
                url: lr_kinsa_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        messageContainer.addClass('success').html(response.data.message).show();
                        
                        // Reset form
                        form[0].reset();
                        
                        // Redirect if URL is provided
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        // Show error message
                        messageContainer.addClass('error').html(response.data.message).show();
                    }
                },
                error: function() {
                    // Show error message
                    messageContainer.addClass('error').html('Ha ocurrido un error. Por favor, inténtalo de nuevo.').show();
                },
                complete: function() {
                    // Re-enable submit button
                    submitButton.prop('disabled', false).text($('.lr-kinsa-submit-button').data('original-text') || 'Enviar');
                }
            });
        });
        
        // Store original submit button text
        $('.lr-kinsa-submit-button').each(function() {
            $(this).data('original-text', $(this).text());
        });
    });
    <?php
    return ob_get_clean();
}

// Handle form submission
function lr_kinsa_submit_form() {
    // Check nonce
    if (!isset($_POST['lr_kinsa_nonce']) || !wp_verify_nonce($_POST['lr_kinsa_nonce'], 'lr_kinsa_submit_form')) {
        wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.'));
        return;
    }
    
    // Get form ID
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    
    if ($form_id <= 0) {
        wp_send_json_error(array('message' => 'ID de formulario inválido.'));
        return;
    }
    
    // Get form data
    $form_data = get_post_meta($form_id, '_lr_kinsa_form_data', true);
    $form_settings = get_post_meta($form_id, '_lr_kinsa_form_settings', true);
    
    if (empty($form_settings)) {
        $form_settings = array(
            'title' => get_the_title($form_id),
            'email_to' => get_option('admin_email'),
            'success_message' => 'Gracias por tu mensaje. Ha sido enviado correctamente.'
        );
    }
    
    // Get correlative number
    $correlative_number = isset($_POST['correlative_number']) ? sanitize_text_field($_POST['correlative_number']) : '';
    
    // Prepare email content
    $subject = 'Nuevo mensaje desde el formulario: ' . $form_settings['title'] . ' - ' . $correlative_number;
    $message = "Has recibido un nuevo mensaje desde el formulario de tu sitio web.\n\n";
    $message .= "Formulario: " . $form_settings['title'] . "\n";
    $message .= "Número de Formulario: " . $correlative_number . "\n\n";
    $message .= "Detalles del mensaje:\n";
    
    // Process form fields
    $form_fields = array();
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'action' && $key !== 'form_id' && $key !== 'lr_kinsa_nonce' && $key !== 'correlative_number') {
            // Find field label from form data
            $field_label = $key;
            foreach ($form_data['fields'] as $field) {
                if (isset($field['name']) && $field['name'] === $key) {
                    $field_label = $field['label'];
                    break;
                }
            }
            
            // Format value
            if (is_array($value)) {
                $formatted_value = implode(', ', $value);
            } else {
                $formatted_value = $value;
            }
            
            // Add to email message
            $message .= $field_label . ": " . $formatted_value . "\n";
            
            // Add to form fields array
            $form_fields[$key] = array(
                'label' => $field_label,
                'value' => $formatted_value
            );
        }
    }
    
    // Save entry to database if enabled
    if (isset($form_settings['save_entries']) && $form_settings['save_entries']) {
        $entry_id = wp_insert_post(array(
            'post_title' => 'Entrada ' . $correlative_number,
            'post_type' => 'lr_kinsa_entry',
            'post_status' => 'publish',
            'meta_input' => array(
                '_lr_kinsa_form_id' => $form_id,
                '_lr_kinsa_correlative_number' => $correlative_number,
                '_lr_kinsa_form_fields' => $form_fields
            )
        ));
    }
    
    // Send email
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    $sent = wp_mail($form_settings['email_to'], $subject, $message, $headers);
    
    if ($sent) {
        $response = array(
            'message' => $form_settings['success_message']
        );
        
        // Add redirect URL if set
        if (isset($form_settings['redirect_url']) && !empty($form_settings['redirect_url'])) {
            $response['redirect_url'] = $form_settings['redirect_url'];
        }
        
        wp_send_json_success($response);
    } else {
        wp_send_json_error(array('message' => 'Ha ocurrido un error al enviar el formulario. Por favor, inténtalo de nuevo más tarde.'));
    }
}
add_action('wp_ajax_lr_kinsa_submit_form', 'lr_kinsa_submit_form');
add_action('wp_ajax_nopriv_lr_kinsa_submit_form', 'lr_kinsa_submit_form');

// Register custom post type for form entries
function lr_kinsa_register_entry_post_type() {
    register_post_type('lr_kinsa_entry', array(
        'labels' => array(
            'name'               => 'Entradas de Formularios',
            'singular_name'      => 'Entrada de Formulario',
            'menu_name'          => 'Entradas',
            'all_items'          => 'Todas las Entradas',
            'view_item'          => 'Ver Entrada',
            'search_items'       => 'Buscar Entradas'
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=lr_kinsa_form',
        'supports'            => array('title'),
        'capability_type'     => 'post',
        'has_archive'         => false
    ));
}
add_action('init', 'lr_kinsa_register_entry_post_type');

// Add meta box for entry details
function lr_kinsa_add_entry_meta_box() {
    add_meta_box(
        'lr_kinsa_entry_details',
        'Detalles de la Entrada',
        'lr_kinsa_entry_details_callback',
        'lr_kinsa_entry',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'lr_kinsa_add_entry_meta_box');

// Entry details callback
function lr_kinsa_entry_details_callback($post) {
    $form_id = get_post_meta($post->ID, '_lr_kinsa_form_id', true);
    $correlative_number = get_post_meta($post->ID, '_lr_kinsa_correlative_number', true);
    $form_fields = get_post_meta($post->ID, '_lr_kinsa_form_fields', true);
    
    $form_title = get_the_title($form_id);
    ?>
    <div class="lr-kinsa-entry-details">
        <p><strong>Formulario:</strong> <?php echo esc_html($form_title); ?></p>
        <p><strong>Número de Formulario:</strong> <?php echo esc_html($correlative_number); ?></p>
        <p><strong>Fecha:</strong> <?php echo get_the_date('d/m/Y H:i:s', $post->ID); ?></p>
        
        <h3>Campos del Formulario</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($form_fields)): ?>
                    <?php foreach ($form_fields as $field): ?>
                        <tr>
                            <td><strong><?php echo esc_html($field['label']); ?></strong></td>
                            <td><?php echo esc_html($field['value']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No hay campos disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <style>
        .lr-kinsa-entry-details {
            margin: 15px 0;
        }
        
        .lr-kinsa-entry-details table {
            margin-top: 15px;
        }
        
        .lr-kinsa-entry-details th,
        .lr-kinsa-entry-details td {
            padding: 8px;
        }
    </style>
    <?php
}

// Add settings page
function lr_kinsa_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=lr_kinsa_form',
        'Configuración',
        'Configuración',
        'manage_options',
        'lr_kinsa_settings',
        'lr_kinsa_settings_page_callback'
    );
}
add_action('admin_menu', 'lr_kinsa_add_settings_page');

// Settings page callback
function lr_kinsa_settings_page_callback() {
    // Save settings
    if (isset($_POST['lr_kinsa_save_settings'])) {
        if (check_admin_referer('lr_kinsa_settings_nonce', 'lr_kinsa_settings_nonce')) {
            update_option('lr_kinsa_recaptcha_site_key', sanitize_text_field($_POST['lr_kinsa_recaptcha_site_key']));
            update_option('lr_kinsa_recaptcha_secret_key', sanitize_text_field($_POST['lr_kinsa_recaptcha_secret_key']));
            
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
        }
    }
    
    $recaptcha_site_key = get_option('lr_kinsa_recaptcha_site_key', '');
    $recaptcha_secret_key = get_option('lr_kinsa_recaptcha_secret_key', '');
    ?>
    <div class="wrap">
        <h1>Configuración del Constructor de Formularios</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('lr_kinsa_settings_nonce', 'lr_kinsa_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">reCAPTCHA Site Key</th>
                    <td>
                        <input type="text" name="lr_kinsa_recaptcha_site_key" value="<?php echo esc_attr($recaptcha_site_key); ?>" class="regular-text">
                        <p class="description">Ingresa tu clave de sitio de reCAPTCHA v2.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">reCAPTCHA Secret Key</th>
                    <td>
                        <input type="text" name="lr_kinsa_recaptcha_secret_key" value="<?php echo esc_attr($recaptcha_secret_key); ?>" class="regular-text">
                        <p class="description">Ingresa tu clave secreta de reCAPTCHA v2.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="lr_kinsa_save_settings" class="button button-primary" value="Guardar Configuración">
            </p>
        </form>
    </div>
    <?php
}

// Asegurarse de que jQuery y jQuery UI estén cargados en el admin
function lr_kinsa_ensure_jquery_ui() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_style('wp-jquery-ui');
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    
    // Cargar el media uploader para seleccionar imágenes
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'lr_kinsa_ensure_jquery_ui');

// Agregar columnas a la lista de formularios
function lr_kinsa_add_form_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns[$key] = $value;
            $new_columns['shortcode'] = 'Shortcode';
        } else {
            $new_columns[$key] = $value;
        }
    }
    
    return $new_columns;
}
add_filter('manage_lr_kinsa_form_posts_columns', 'lr_kinsa_add_form_columns');

// Mostrar contenido de columnas personalizadas
function lr_kinsa_form_custom_column($column, $post_id) {
    if ($column === 'shortcode') {
        echo '<code>[lr_kinsa_form id="' . $post_id . '"]</code>';
    }
}
add_action('manage_lr_kinsa_form_posts_custom_column', 'lr_kinsa_form_custom_column', 10, 2);

// Agregar columnas a la lista de entradas
function lr_kinsa_add_entry_columns($columns) {
    $new_columns = array(
        'cb' => $columns['cb'],
        'title' => $columns['title'],
        'form' => 'Formulario',
        'correlative' => 'Número',
        'date' => $columns['date']
    );
    
    return $new_columns;
}
add_filter('manage_lr_kinsa_entry_posts_columns', 'lr_kinsa_add_entry_columns');

// Mostrar contenido de columnas personalizadas para entradas
function lr_kinsa_entry_custom_column($column, $post_id) {
    if ($column === 'form') {
        $form_id = get_post_meta($post_id, '_lr_kinsa_form_id', true);
        echo get_the_title($form_id);
    } elseif ($column === 'correlative') {
        echo get_post_meta($post_id, '_lr_kinsa_correlative_number', true);
    }
}
add_action('manage_lr_kinsa_entry_posts_custom_column', 'lr_kinsa_entry_custom_column', 10, 2);

// Hacer que las columnas sean ordenables
function lr_kinsa_entry_sortable_columns($columns) {
    $columns['form'] = 'form';
    $columns['correlative'] = 'correlative';
    return $columns;
}
add_filter('manage_edit-lr_kinsa_entry_sortable_columns', 'lr_kinsa_entry_sortable_columns');

// Ordenar por columnas personalizadas
function lr_kinsa_entry_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') === 'lr_kinsa_entry') {
        if ($query->get('orderby') === 'form') {
            $query->set('meta_key', '_lr_kinsa_form_id');
            $query->set('orderby', 'meta_value_num');
        } elseif ($query->get('orderby') === 'correlative') {
            $query->set('meta_key', '_lr_kinsa_correlative_number');
            $query->set('orderby', 'meta_value');
        }
    }
}
add_action('pre_get_posts', 'lr_kinsa_entry_orderby');

// Agregar filtro por formulario en la lista de entradas
function lr_kinsa_add_form_filter() {
    global $typenow;
    
    if ($typenow === 'lr_kinsa_entry') {
        $forms = get_posts(array(
            'post_type' => 'lr_kinsa_form',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $selected = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        echo '<select name="form_id">';
        echo '<option value="0">' . __('Todos los formularios') . '</option>';
        
        foreach ($forms as $form) {
            echo '<option value="' . $form->ID . '" ' . selected($selected, $form->ID, false) . '>' . esc_html($form->post_title) . '</option>';
        }
        
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'lr_kinsa_add_form_filter');

// Filtrar entradas por formulario
function lr_kinsa_filter_entries_by_form($query) {
    global $pagenow, $typenow;
    
    if ($pagenow === 'edit.php' && $typenow === 'lr_kinsa_entry' && isset($_GET['form_id']) && intval($_GET['form_id']) > 0) {
        $query->query_vars['meta_key'] = '_lr_kinsa_form_id';
        $query->query_vars['meta_value'] = intval($_GET['form_id']);
    }
}
add_action('pre_get_posts', 'lr_kinsa_filter_entries_by_form');

// Exportar entradas a CSV
function lr_kinsa_export_entries() {
    if (isset($_GET['export_entries']) && isset($_GET['form_id']) && current_user_can('manage_options')) {
        $form_id = intval($_GET['form_id']);
        
        if ($form_id <= 0) {
            return;
        }
        
        // Get form data
        $form_data = get_post_meta($form_id, '_lr_kinsa_form_data', true);
        $form_title = get_the_title($form_id);
        
        // Get entries
        $entries = get_posts(array(
            'post_type' => 'lr_kinsa_entry',
            'posts_per_page' => -1,
            'meta_key' => '_lr_kinsa_form_id',
            'meta_value' => $form_id,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($entries)) {
            wp_redirect(admin_url('edit.php?post_type=lr_kinsa_entry&message=no-entries'));
            exit;
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . sanitize_file_name($form_title) . '-entradas-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Get all field names
        $field_names = array('ID', 'Fecha', 'Número de Formulario');
        $field_keys = array();
        
        // Get first entry to extract field keys
        if (!empty($entries)) {
            $first_entry = $entries[0];
            $form_fields = get_post_meta($first_entry->ID, '_lr_kinsa_form_fields', true);
            
            if (!empty($form_fields)) {
                foreach ($form_fields as $key => $field) {
                    $field_names[] = $field['label'];
                    $field_keys[] = $key;
                }
            }
        }
        
        // Write header row
        fputcsv($output, $field_names);
        
        // Write data rows
        foreach ($entries as $entry) {
            $row = array(
                $entry->ID,
                get_the_date('d/m/Y H:i:s', $entry->ID),
                get_post_meta($entry->ID, '_lr_kinsa_correlative_number', true)
            );
            
            $form_fields = get_post_meta($entry->ID, '_lr_kinsa_form_fields', true);
            
            foreach ($field_keys as $key) {
                if (isset($form_fields[$key])) {
                    $row[] = $form_fields[$key]['value'];
                } else {
                    $row[] = '';
                }
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
add_action('admin_init', 'lr_kinsa_export_entries');

// Agregar botón de exportar en la página de entradas
function lr_kinsa_add_export_button() {
    global $typenow;
    
    if ($typenow === 'lr_kinsa_entry' && isset($_GET['form_id']) && intval($_GET['form_id']) > 0) {
        $form_id = intval($_GET['form_id']);
        $export_url = admin_url('edit.php?post_type=lr_kinsa_entry&export_entries=1&form_id=' . $form_id);
        
        echo '<a href="' . esc_url($export_url) . '" class="button" style="margin-left: 5px;">Exportar a CSV</a>';
    }
}
add_action('restrict_manage_posts', 'lr_kinsa_add_export_button', 20);

// Mostrar mensajes de admin
function lr_kinsa_admin_notices() {
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'lr_kinsa_entry' && isset($_GET['message']) && $_GET['message'] === 'no-entries') {
        echo '<div class="notice notice-error is-dismissible"><p>No hay entradas para exportar.</p></div>';
    }
}
add_action('admin_notices', 'lr_kinsa_admin_notices');


// Función para guardar el formulario mediante AJAX
function lr_kinsa_save_form_ajax_callback() {
    // Verificar nonce
    if (!isset($_POST['lr_kinsa_form_nonce']) || !wp_verify_nonce($_POST['lr_kinsa_form_nonce'], 'lr_kinsa_save_form')) {
        wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página e intenta de nuevo.'));
        return;
    }
    
    // Verificar permisos - Cambiado para permitir a editores también guardar
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'No tienes permisos suficientes para realizar esta acción.'));
        return;
    }
    
    // Obtener ID del formulario
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    
    // Verificar que el formulario existe
    if (!get_post($form_id) || get_post_type($form_id) !== 'lr_kinsa_form') {
        wp_send_json_error(array('message' => 'El formulario no existe.'));
        return;
    }
    
    // Obtener datos del formulario
    $form_data = isset($_POST['lr_kinsa_form_data']) ? $_POST['lr_kinsa_form_data'] : '';
    
    // Validar datos JSON
    $decoded_data = json_decode($form_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('message' => 'Error al procesar los datos del formulario. Por favor, intenta de nuevo.'));
        return;
    }
    
    // Guardar datos del formulario - FORZAR LA ACTUALIZACIÓN
    $updated = update_post_meta($form_id, '_lr_kinsa_form_data', $decoded_data);
    
    // Forzar la actualización del post para asegurar que WordPress reconoce el cambio
    wp_update_post(array('ID' => $form_id));
    
    // Enviar respuesta de éxito
    wp_send_json_success(array('message' => 'Formulario guardado correctamente.'));
}
add_action('wp_ajax_lr_kinsa_save_form_ajax', 'lr_kinsa_save_form_ajax_callback');
