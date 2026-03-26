<?php

Kirby::plugin('cjcfe/contact-form', [
    'api' => [
        'routes' => [
            [
                'pattern' => 'contact/submit',
                'method'  => 'POST',
                'auth'    => false,
                'action'  => function () {
                    $kirby = kirby();
                    $body   = $kirby->request()->body();

                    // Protection honeypot
                    if (!empty($body->get('website'))) {
                        return ['status' => 'ok'];
                    }

                    $formData = [
                        'name'    => trim($body->get('name', '')),
                        'email'   => trim($body->get('email', '')),
                        'phone'   => trim($body->get('phone', '')),
                        'subject' => trim($body->get('subject', '')),
                        'message' => trim($body->get('message', '')),
                    ];

                    // Validation
                    $rules = [
                        'name'    => ['required', 'minLength' => 2],
                        'email'   => ['required', 'email'],
                        'message' => ['required', 'minLength' => 10],
                    ];

                    $messages = [
                        'name'    => 'Veuillez entrer votre nom',
                        'email'   => 'Veuillez entrer une adresse email valide',
                        'message' => 'Votre message doit contenir au moins 10 caractères',
                    ];

                    if ($invalid = invalid($formData, $rules, $messages)) {
                        return [
                            'status' => 'error',
                            'errors' => $invalid,
                        ];
                    }

                    $emailTo   = option('cjcfe.contact.to', 'contact@cjcfe.fr');
                    $emailFrom = option('cjcfe.contact.from', 'noreply@cjcfe.fr');

                    try {
                        // 1. Email de notification au propriétaire du site
                        $kirby->email([
                            'template' => 'contact',
                            'from'     => $emailFrom,
                            'replyTo'  => $formData['email'],
                            'to'       => $emailTo,
                            'subject'  => 'Nouveau message de contact' . (!empty($formData['subject']) ? ' : ' . $formData['subject'] : ''),
                            'data'     => [
                                'name'    => esc($formData['name']),
                                'email'   => esc($formData['email']),
                                'phone'   => esc($formData['phone']),
                                'subject' => esc($formData['subject']),
                                'message' => esc($formData['message']),
                                'site'    => $kirby->site(),
                            ],
                        ]);

                        // 2. Email de confirmation à l'utilisateur
                        try {
                            $kirby->email([
                                'template' => 'confirmation',
                                'from'     => $emailFrom,
                                'to'       => $formData['email'],
                                'subject'  => 'Confirmation de votre message - CJCFE',
                                'data'     => [
                                    'name'    => esc($formData['name']),
                                    'subject' => esc($formData['subject']),
                                    'message' => esc($formData['message']),
                                    'site'    => $kirby->site(),
                                ],
                            ]);
                        } catch (Exception $confirmError) {
                            // Log l'erreur mais ne bloque pas
                            error_log('Erreur email confirmation: ' . $confirmError->getMessage());
                        }

                        return ['status' => 'ok'];

                    } catch (Exception $e) {
                        if (option('debug') === true) {
                            return [
                                'status'  => 'error',
                                'message' => $e->getMessage(),
                            ];
                        }
                        return [
                            'status'  => 'error',
                            'message' => "Une erreur est survenue lors de l'envoi.",
                        ];
                    }
                },
            ],
        ],
    ],
]);
