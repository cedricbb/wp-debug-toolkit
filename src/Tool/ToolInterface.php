<?php

namespace WPDebugToolkit\Tool;

interface ToolInterface
{
    /**
     * Initialiser l'outil
     */
    public function init(): void;

    /**
     * Récupérer l'ID de l'outil
     */
    public function getId(): string;

    /**
     * Récupérer le titre de l'outil
     */
    public function getTitle(): string;

    /**
     * Récupérer la description de l'outil
     */
    public function getDescription(): string;

    /**
     * Récupérer l'icône de l'outil
     */
    public function getIcon(): string;

    /**
     * Afficher le contenu de l'outil
     */
    public function renderContent(): void;
}
