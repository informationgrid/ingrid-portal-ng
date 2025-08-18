---
title: Kontakt Formular
routes:
  default: '/contact/form'
forms:
  contact-form:
    fields:

      - name: message
        type: honeypot

      - name: name
        type: honeypot

      - name: email
        type: honeypot

      - name: user_subject
        label: CONTACT.FORM_SUBJECT
        placeholder: CONTACT.FORM_SUBJECT
        type: text
        outerclasses: form-element
        validate:
          required: true

      - name: user_message
        label: CONTACT.FORM_MESSAGE
        size: long
        placeholder: CONTACT.FORM_MESSAGE
        autofocus: on
        type: textarea
        outerclasses: form-element
        validate:
          required: true

      - name: user_technical
        label: CONTACT.FORM_TECHNICAL
        placeholder: CONTACT.FORM_TECHNICAL
        type: checkbox_label
        outerclasses: form-element
        wrapper_classes: control-group
        label_classes: control control--checkbox field-toggle__label field-toggle__label--boxed
        on_change: ingrid_disableElementByCheckbox('user_technical', 'user_company')

      - name: user_company
        label: CONTACT.FORM_COMPANY
        type: select
        outerclasses: form-element
        options:
          false: ---
          bb: Brandenburg
          hb: Bremen
          hh: Hamburg
          mv: Mecklenburg-Vorpommern
          sl: Saarland
          sn: Sachsen
          sa: Sachsen-Anhalt

      - name: user_email
        label: CONTACT.FORM_EMAIL
        placeholder: CONTACT.FORM_EMAIL
        type: email
        outerclasses: form-element
        validate:
          rule: email
          required: true

      - name: user_name
        label: CONTACT.FORM_NAME
        placeholder: CONTACT.FORM_NAME
        type: text
        outerclasses: form-element

    buttons:
      - type: submit
        value: COMMON.FORM_BUTTON_SUBMIT
        outerclasses: subtext-submit
        classes: button

    process:
      - email:
          from: "{{ config.plugins.email.from }}"
          to:
            - "{{ config.plugins.email.to }}"
            - "{{ form.value.user_email }}"
          reply_to:
            - "{{ form.value.user_email }}"
          subject: "{{ form.value.user_subject }}"
          body: "{% include 'forms/contact/contact.email.html.twig' %}"
      - save:
          fileprefix: contact-
          dateformat: Ymd-His-u
          extension: txt
          body: "{% include 'forms/data.txt.twig' %}"
      - message: CONTACT.SUCCESS
      - display: "/contact/success"
---