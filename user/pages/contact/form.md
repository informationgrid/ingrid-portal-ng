---
title: PAGES.CONTACT
visible: true
routes:
  default: '/kontakt'
form:
    name: contact-form
    classes: form
    fields:

        - name: message
          label: message
          autofocus: off
          autocomplete: off
          type: hidden

        - name: name
          label: name
          autofocus: off
          autocomplete: off
          type: hidden

        - name: email
          label: email
          autofocus: off
          autocomplete: off
          type: hidden

        - name: user_message
          label: CONTACT.FORM_MESSAGE
          size: long
          placeholder: CONTACT.FORM_MESSAGE
          type: textarea
          outerclasses: form-element
          validate:
            required: true

        - name: user_firstname
          label: CONTACT.FORM_FIRSTNAME
          placeholder: CONTACT.FORM_FIRSTNAME
          autofocus: on
          autocomplete: on
          type: text
          outerclasses: form-element
          validate:
            required: true

        - name: user_lastname
          label: CONTACT.FORM_LASTNAME
          placeholder: CONTACT.FORM_LASTNAME
          autofocus: on
          autocomplete: on
          type: text
          outerclasses: form-element
          validate:
            required: true

        - name: user_organisation
          label: CONTACT.FORM_COMPANY
          placeholder: CONTACT.FORM_COMPANY
          autofocus: on
          autocomplete: on
          type: text
          outerclasses: form-element

        - name: user_email
          label: CONTACT.FORM_EMAIL
          placeholder: CONTACT.FORM_EMAIL
          type: text
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
            from: "{{ config.plugins.email.from }}"
            to:
              - "{{ config.plugins.email.from }}"
              - "{{ form.value.email }}"
            subject: CONTACT.REPORT_EMAIL_SUBJECT
            body: "{% include 'forms/contact/contact.email.html.twig' %}"
        - save:
            fileprefix: contact-
            dateformat: Ymd-His-u
            extension: txt
            body: "{% include 'forms/data.txt.twig' %}"
        - message: CONTACT.SUCCESS
        - display: success
---

# Page Titlewemove

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque porttitor eu
felis sed ornare. Sed a mauris venenatis, pulvinar velit vel, dictum enim. Phasellus
ac rutrum velit. **Nunc lorem** purus, hendrerit sit amet augue aliquet, iaculis
ultricies nisl. Suspendisse tincidunt euismod risus, _quis feugiat_ arcu tincidunt
eget. Nulla eros mi, commodo vel ipsum vel, aliquet congue odio. Class aptent taciti
sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Pellentesque
velit orci, laoreet at adipiscing eu, interdum quis nibh. Nunc a accumsan purus.



