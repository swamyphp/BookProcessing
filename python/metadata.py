#!/usr/bin/env python3
from docx import Document

def extract_metadata(doc_path):
    try:
        doc = Document(doc_path)
        props = doc.core_properties
        return {'title': props.title, 'author': props.author}
    except Exception:
        return {'title': None, 'author': None}

if __name__=='__main__':
    print('metadata')
