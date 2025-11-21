<?php
/**
 * Loader de hooks del plugin
 *
 * @since 1.0.0
 */
class Sunat_Facturacion_Loader {

    /**
     * Array de actions registrados
     *
     * @since 1.0.0
     * @var array
     */
    protected $actions;

    /**
     * Array de filters registrados
     *
     * @since 1.0.0
     * @var array
     */
    protected $filters;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Agregar action
     *
     * @since 1.0.0
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Agregar filter
     *
     * @since 1.0.0
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Agregar hook al array
     *
     * @since 1.0.0
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Ejecutar todos los hooks
     *
     * @since 1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
