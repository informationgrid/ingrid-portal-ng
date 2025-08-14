---
title: Kontakt Formular
routes:
  default: '/contact/form'
forms:
  contact-form:
    fields:
      message:
        label: message
        autofocus: off
        autocomplete: off
        type: hidden

      name:
        label: name
        autofocus: off
        autocomplete: off
        type: hidden

      email:
        label: email
        autofocus: off
        autocomplete: off
        type: hidden

      user_message:
        label: CONTACT.FORM_MESSAGE
        size: long
        placeholder: CONTACT.FORM_MESSAGE
        autofocus: on
        type: textarea
        outerclasses: form-element
        validate:
          required: true

      user_topic:
        label: CONTACT.FORM_TOPIC
        type: select
        outerclasses: form-element
        options:
          atlas: VIA.WSV
          schul: GeoPortal.WSV
          hand: GeoKatalog.WSV (Fragen/Anmeldung)
          umwe: GeoKatalog.WSV (Fragen/Anmeldung)
          beho: DesktopGIS.WSV (ArcGIS, Citrix)
          lehre: DesktopGIS.WSV (ArcGIS, Citrix)
          univ: BWaStrLocator
          medi: GeoTools.WSV
          map: Kartenclient
          buhnen: BuhnenGIS.WSV
        validate:
          required: true

      user_firstname:
        label: CONTACT.FORM_FIRSTNAME
        placeholder: CONTACT.FORM_FIRSTNAME
        autocomplete: on
        type: text
        outerclasses: form-element
        validate:
          required: true

      user_lastname:
        label: CONTACT.FORM_LASTNAME
        placeholder: CONTACT.FORM_LASTNAME
        autocomplete: on
        type: text
        outerclasses: form-element
        validate:
          required: true

      user_email:
        label: CONTACT.FORM_EMAIL
        placeholder: CONTACT.FORM_EMAIL
        type: email
        outerclasses: form-element
        validate:
          rule: email
          required: true

      user_organisation:
        label: CONTACT.FORM_COMPANY
        placeholder: CONTACT.FORM_COMPANY
        autocomplete: on
        type: text
        outerclasses: form-element

      user_phone:
        label: CONTACT.FORM_PHONE
        placeholder: CONTACT.FORM_PHONE
        type: text
        outerclasses: form-element

      user_file:
        label: CONTACT.FORM_UPLOAD
        type: file
        multiple: false
        destination: 'user/data/contact-form/files'
        filesize: 10
        avoid_overwriting: false
        random_name: false
        accept:
          - '*'

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
          subject: CONTACT.REPORT_EMAIL_SUBJECT
          body: "{% include 'forms/contact/contact.email.html.twig' %}"
          attachments:
            - user_file
      - save:
          fileprefix: contact-
          dateformat: Ymd-His-u
          extension: txt
          body: "{% include 'forms/data.txt.twig' %}"
      - message: CONTACT.SUCCESS
      - display: "/contact/success"
---