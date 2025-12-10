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

      - name: user_message
        label: CONTACT.FORM_MESSAGE
        size: long
        placeholder: CONTACT.FORM_MESSAGE
        autofocus: on
        type: textarea
        outerclasses: form-element
        validate:
          required: true

      - name: user_firstname
        label: CONTACT.FORM_FIRSTNAME
        placeholder: CONTACT.FORM_FIRSTNAME
        autocomplete: on
        type: text
        outerclasses: form-element
        validate:
          required: true

      - name: user_lastname
        label: CONTACT.FORM_LASTNAME
        placeholder: CONTACT.FORM_LASTNAME
        autocomplete: on
        type: text
        outerclasses: form-element
        validate:
          required: true

      - name: user_organisation
        label: CONTACT.FORM_COMPANY
        placeholder: CONTACT.FORM_COMPANY
        autocomplete: on
        type: text
        outerclasses: form-element

      - name: user_email
        label: CONTACT.FORM_EMAIL
        placeholder: CONTACT.FORM_EMAIL
        type: email
        outerclasses: form-element
        validate:
          rule: email
          required: true

      - name: user_phone
        label: CONTACT.FORM_PHONE
        placeholder: CONTACT.FORM_PHONE
        type: text
        outerclasses: form-element

    buttons:
      - type: submit
        value: COMMON.FORM_BUTTON_SUBMIT
        outerclasses: subtext-submit
        classes: button

    process:
      - email:
          from: "{{ form.value.user_email }}"
          to:
            - "{{ config.plugins.email.to }}"
            - "{{ form.value.user_email }}"
          reply_to:
            - "{{ form.value.user_email }}"
          subject: CONTACT.REPORT_EMAIL_SUBJECT
          body: "{% include 'forms/contact/contact.email.html.twig' %}"
      - save:
          fileprefix: contact-
          dateformat: Ymd-His-u
          extension: txt
          body: "{% include 'forms/data.txt.twig' %}"
      - message: CONTACT.SUCCESS
      - display: "/contact/success"
---