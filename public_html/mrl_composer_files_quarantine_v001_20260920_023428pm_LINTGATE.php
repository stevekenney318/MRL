<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');

const REAL_FILE_NAME = 'mrl_composer_files_quarantine_v001_20260920_023428pm.php';
const REAL_FILE_SHA256 = 'e6ebdb2fb58def88191a3bb2603c498db87b5f3e9bdb939eb9f9a253599a9bc7';
const REAL_FILE_B64 = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogbXJsX2NvbXBvc2VyX2ZpbGVzX3F1YXJhbnRpbmVfdjAwMV8yMDI2MDkyMF8wMjM0MjhwbS5waHAKICoKICogVkVSU0lPTjogdjAwMQogKiBHRU5FUkFURUQ6IDkvMjAvMjAyNiAyOjM0OjI4IHBtIEVUCiAqCiAqIFBVUlBPU0U6CiAqIC0gUmV2ZXJzaWJseSBxdWFyYW50aW5lIHJvb3QgY29tcG9zZXIuanNvbiBhbmQgY29tcG9zZXIubG9jay4KICogLSBLZWVwcyB0aGUgcGFpciB0b2dldGhlciB1bmRlciAvcHVibGljX2h0bWwvX3F1YXJhbnRpbmUvLgogKiAtIFByb3ZpZGVzIHJlc3RvcmUuCiAqIC0gRG9lcyBOT1QgdG91Y2ggcm9vdCAvdmVuZG9yLCBXb3JkUHJlc3MgdmVuZG9yIHRyZWVzLCBvciBhbnkgUEhQIGNvZGUuCiAqLwoKZGF0ZV9kZWZhdWx0X3RpbWV6b25lX3NldCgnQW1lcmljYS9OZXdfWW9yaycpOwoKZnVuY3Rpb24gaCgkdmFsdWUpOiBzdHJpbmcKewogICAgcmV0dXJuIGh0bWxzcGVjaWFsY2hhcnMoKHN0cmluZykkdmFsdWUsIEVOVF9RVU9URVMsICdVVEYtOCcpOwp9CgokZG9jUm9vdCA9IHJ0cmltKChzdHJpbmcpKCRfU0VSVkVSWydET0NVTUVOVF9ST09UJ10gPz8gX19ESVJfXyksICcvXFwnKTsKCiRzb3VyY2VKc29uID0gJGRvY1Jvb3QgLiAnL2NvbXBvc2VyLmpzb24nOwokc291cmNlTG9jayA9ICRkb2NSb290IC4gJy9jb21wb3Nlci5sb2NrJzsKCiRxdWFyYW50aW5lUm9vdCA9ICRkb2NSb290IC4gJy9fcXVhcmFudGluZSc7CiRxdWFyYW50aW5lRGlyID0gJHF1YXJhbnRpbmVSb290IC4gJy9jb21wb3Nlcl8yMDI2MDkyMF8wMjM0MjhwbSc7CiRkZXN0SnNvbiA9ICRxdWFyYW50aW5lRGlyIC4gJy9jb21wb3Nlci5qc29uJzsKJGRlc3RMb2NrID0gJHF1YXJhbnRpbmVEaXIgLiAnL2NvbXBvc2VyLmxvY2snOwoKJGFjdGlvbiA9IChzdHJpbmcpKCRfUE9TVFsnYWN0aW9uJ10gPz8gJycpOwokbWVzc2FnZSA9ICcnOwokbWVzc2FnZUNsYXNzID0gJ2luZm8nOwoKZnVuY3Rpb24gZmlsZV9zdGF0ZShzdHJpbmcgJHNvdXJjZSwgc3RyaW5nICRkZXN0KTogYXJyYXkKewogICAgcmV0dXJuIFsKICAgICAgICAnc291cmNlJyA9PiBpc19maWxlKCRzb3VyY2UpLAogICAgICAgICdkZXN0JyA9PiBpc19maWxlKCRkZXN0KSwKICAgIF07Cn0KCiRzdGF0ZUpzb24gPSBmaWxlX3N0YXRlKCRzb3VyY2VKc29uLCAkZGVzdEpzb24pOwokc3RhdGVMb2NrID0gZmlsZV9zdGF0ZSgkc291cmNlTG9jaywgJGRlc3RMb2NrKTsKCiRjYW5RdWFyYW50aW5lID0KICAgIGlzX2ZpbGUoJHNvdXJjZUpzb24pCiAgICAmJiBpc19maWxlKCRzb3VyY2VMb2NrKQogICAgJiYgIWlzX2ZpbGUoJGRlc3RKc29uKQogICAgJiYgIWlzX2ZpbGUoJGRlc3RMb2NrKQogICAgJiYgaXNfd3JpdGFibGUoJGRvY1Jvb3QpOwoKJGNhblJlc3RvcmUgPQogICAgIWlzX2ZpbGUoJHNvdXJjZUpzb24pCiAgICAmJiAhaXNfZmlsZSgkc291cmNlTG9jaykKICAgICYmIGlzX2ZpbGUoJGRlc3RKc29uKQogICAgJiYgaXNfZmlsZSgkZGVzdExvY2spCiAgICAmJiBpc193cml0YWJsZSgkZG9jUm9vdCk7CgppZiAoJGFjdGlvbiA9PT0gJ3F1YXJhbnRpbmUnKSB7CiAgICBpZiAoISRjYW5RdWFyYW50aW5lKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnUXVhcmFudGluZSBibG9ja2VkOiBwcmVmbGlnaHQgY29uZGl0aW9ucyBhcmUgbm90IHNhdGlzZmllZC4nOwogICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnYmFkJzsKICAgIH0gZWxzZSB7CiAgICAgICAgaWYgKCFpc19kaXIoJHF1YXJhbnRpbmVEaXIpICYmICFAbWtkaXIoJHF1YXJhbnRpbmVEaXIsIDA3NTUsIHRydWUpICYmICFpc19kaXIoJHF1YXJhbnRpbmVEaXIpKSB7CiAgICAgICAgICAgICRtZXNzYWdlID0gJ1F1YXJhbnRpbmUgZmFpbGVkOiBjb3VsZCBub3QgY3JlYXRlIHF1YXJhbnRpbmUgZGlyZWN0b3J5Lic7CiAgICAgICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnYmFkJzsKICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAkbW92ZWRKc29uID0gQHJlbmFtZSgkc291cmNlSnNvbiwgJGRlc3RKc29uKTsKCiAgICAgICAgICAgIGlmICghJG1vdmVkSnNvbikgewogICAgICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUXVhcmFudGluZSBmYWlsZWQ6IGNvbXBvc2VyLmpzb24gY291bGQgbm90IGJlIG1vdmVkLic7CiAgICAgICAgICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2JhZCc7CiAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICAkbW92ZWRMb2NrID0gQHJlbmFtZSgkc291cmNlTG9jaywgJGRlc3RMb2NrKTsKCiAgICAgICAgICAgICAgICBpZiAoISRtb3ZlZExvY2spIHsKICAgICAgICAgICAgICAgICAgICBAcmVuYW1lKCRkZXN0SnNvbiwgJHNvdXJjZUpzb24pOwogICAgICAgICAgICAgICAgICAgICRtZXNzYWdlID0gJ1F1YXJhbnRpbmUgZmFpbGVkOiBjb21wb3Nlci5sb2NrIGNvdWxkIG5vdCBiZSBtb3ZlZC4gY29tcG9zZXIuanNvbiB3YXMgYXV0b21hdGljYWxseSByZXN0b3JlZC4nOwogICAgICAgICAgICAgICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnYmFkJzsKICAgICAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUEFTUyDigJQgY29tcG9zZXIuanNvbiBhbmQgY29tcG9zZXIubG9jayBtb3ZlZCBpbnRvIHF1YXJhbnRpbmUuIE5vdGhpbmcgd2FzIGRlbGV0ZWQuJzsKICAgICAgICAgICAgICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2dvb2QnOwogICAgICAgICAgICAgICAgfQogICAgICAgICAgICB9CiAgICAgICAgfQogICAgfQp9CgppZiAoJGFjdGlvbiA9PT0gJ3Jlc3RvcmUnKSB7CiAgICBpZiAoISRjYW5SZXN0b3JlKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnUmVzdG9yZSBibG9ja2VkOiBwcmVmbGlnaHQgY29uZGl0aW9ucyBhcmUgbm90IHNhdGlzZmllZC4nOwogICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnYmFkJzsKICAgIH0gZWxzZSB7CiAgICAgICAgJHJlc3RvcmVkSnNvbiA9IEByZW5hbWUoJGRlc3RKc29uLCAkc291cmNlSnNvbik7CgogICAgICAgIGlmICghJHJlc3RvcmVkSnNvbikgewogICAgICAgICAgICAkbWVzc2FnZSA9ICdSZXN0b3JlIGZhaWxlZDogY29tcG9zZXIuanNvbiBjb3VsZCBub3QgYmUgcmVzdG9yZWQuJzsKICAgICAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICRyZXN0b3JlZExvY2sgPSBAcmVuYW1lKCRkZXN0TG9jaywgJHNvdXJjZUxvY2spOwoKICAgICAgICAgICAgaWYgKCEkcmVzdG9yZWRMb2NrKSB7CiAgICAgICAgICAgICAgICBAcmVuYW1lKCRzb3VyY2VKc29uLCAkZGVzdEpzb24pOwogICAgICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUmVzdG9yZSBmYWlsZWQ6IGNvbXBvc2VyLmxvY2sgY291bGQgbm90IGJlIHJlc3RvcmVkLiBjb21wb3Nlci5qc29uIHdhcyBtb3ZlZCBiYWNrIGludG8gcXVhcmFudGluZS4nOwogICAgICAgICAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUEFTUyDigJQgY29tcG9zZXIuanNvbiBhbmQgY29tcG9zZXIubG9jayByZXN0b3JlZCBmcm9tIHF1YXJhbnRpbmUuJzsKICAgICAgICAgICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnZ29vZCc7CiAgICAgICAgICAgIH0KICAgICAgICB9CiAgICB9Cn0KCiRzdGF0ZUpzb24gPSBmaWxlX3N0YXRlKCRzb3VyY2VKc29uLCAkZGVzdEpzb24pOwokc3RhdGVMb2NrID0gZmlsZV9zdGF0ZSgkc291cmNlTG9jaywgJGRlc3RMb2NrKTsKCiRjYW5RdWFyYW50aW5lID0KICAgIGlzX2ZpbGUoJHNvdXJjZUpzb24pCiAgICAmJiBpc19maWxlKCRzb3VyY2VMb2NrKQogICAgJiYgIWlzX2ZpbGUoJGRlc3RKc29uKQogICAgJiYgIWlzX2ZpbGUoJGRlc3RMb2NrKQogICAgJiYgaXNfd3JpdGFibGUoJGRvY1Jvb3QpOwoKJGNhblJlc3RvcmUgPQogICAgIWlzX2ZpbGUoJHNvdXJjZUpzb24pCiAgICAmJiAhaXNfZmlsZSgkc291cmNlTG9jaykKICAgICYmIGlzX2ZpbGUoJGRlc3RKc29uKQogICAgJiYgaXNfZmlsZSgkZGVzdExvY2spCiAgICAmJiBpc193cml0YWJsZSgkZG9jUm9vdCk7Cgo/Pgo8IWRvY3R5cGUgaHRtbD4KPGh0bWwgbGFuZz0iZW4iPgo8aGVhZD4KPG1ldGEgY2hhcnNldD0idXRmLTgiPgo8bWV0YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLGluaXRpYWwtc2NhbGU9MSI+Cjx0aXRsZT5NUkwgQ29tcG9zZXIgRmlsZXMgUXVhcmFudGluZTwvdGl0bGU+CjxzdHlsZT4KOnJvb3R7Y29sb3Itc2NoZW1lOmRhcms7LS1iZzojMTAxMDEwOy0tcGFuZWw6IzFiMWIxYjstLWxpbmU6IzNkM2QzZDstLXRleHQ6I2VlZTstLW11dGVkOiNhYWE7LS1nb29kOiM2NmRmOGQ7LS1iYWQ6I2ZmNzQ3NDstLWdvbGQ6I2YyYzk4ZX0KKntib3gtc2l6aW5nOmJvcmRlci1ib3h9CmJvZHl7bWFyZ2luOjA7cGFkZGluZzoyMnB4O2JhY2tncm91bmQ6dmFyKC0tYmcpO2NvbG9yOnZhcigtLXRleHQpO2ZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmfQoud3JhcHttYXgtd2lkdGg6MTAwMHB4O21hcmdpbjowIGF1dG99Cmgxe21hcmdpbjowIDAgNnB4O2NvbG9yOnZhcigtLWdvbGQpfQpoMnttYXJnaW46MCAwIDEwcHh9Ci5jYXJke21hcmdpbjoxNHB4IDA7cGFkZGluZzoxNnB4O2JhY2tncm91bmQ6dmFyKC0tcGFuZWwpO2JvcmRlcjoxcHggc29saWQgdmFyKC0tbGluZSk7Ym9yZGVyLXJhZGl1czoxNHB4fQoubm90aWNle21hcmdpbjoxNHB4IDA7cGFkZGluZzoxMnB4IDE0cHg7Ym9yZGVyLXJhZGl1czoxMHB4O2JvcmRlcjoxcHggc29saWQgIzM2NTA2ZjtiYWNrZ3JvdW5kOiMxNDIwMzN9Ci5ub3RpY2UuZ29vZHtib3JkZXItY29sb3I6IzMyN2E0YjtiYWNrZ3JvdW5kOiMxMzI3MWE7Y29sb3I6I2E4ZWZiZn0KLm5vdGljZS5iYWR7Ym9yZGVyLWNvbG9yOiM5ODNmM2Y7YmFja2dyb3VuZDojMmExNTE1O2NvbG9yOiNmZmIwYjB9Ci5nb29ke2NvbG9yOnZhcigtLWdvb2QpO2ZvbnQtd2VpZ2h0OjgwMH0uYmFke2NvbG9yOnZhcigtLWJhZCk7Zm9udC13ZWlnaHQ6ODAwfS5tdXRlZHtjb2xvcjp2YXIoLS1tdXRlZCl9CmNvZGV7YmFja2dyb3VuZDojMjgyODI4O3BhZGRpbmc6MnB4IDVweDtib3JkZXItcmFkaXVzOjVweDt3b3JkLWJyZWFrOmJyZWFrLXdvcmR9CnRhYmxle3dpZHRoOjEwMCU7Ym9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlfQp0aCx0ZHtwYWRkaW5nOjlweCAxMHB4O2JvcmRlci1ib3R0b206MXB4IHNvbGlkICMzMzM7dGV4dC1hbGlnbjpsZWZ0O3ZlcnRpY2FsLWFsaWduOnRvcH0KdGh7Y29sb3I6dmFyKC0tZ29sZCl9Ci5idXR0b25ze2Rpc3BsYXk6ZmxleDtmbGV4LXdyYXA6d3JhcDtnYXA6MTBweDttYXJnaW4tdG9wOjE0cHh9CmJ1dHRvbntib3JkZXI6MDtib3JkZXItcmFkaXVzOjlweDtwYWRkaW5nOjEwcHggMTZweDtjb2xvcjojZmZmO2ZvbnQtd2VpZ2h0OjcwMDtjdXJzb3I6cG9pbnRlcjtmb250LXNpemU6MTRweH0KLnF1YXJhbnRpbmV7YmFja2dyb3VuZDojYjA2YTE4fS5yZXN0b3Jle2JhY2tncm91bmQ6IzI3NmZjYX0KYnV0dG9uOmRpc2FibGVke29wYWNpdHk6LjM4O2N1cnNvcjpub3QtYWxsb3dlZH0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KPGRpdiBjbGFzcz0id3JhcCI+CiAgICA8aDE+TVJMIENvbXBvc2VyIEZpbGVzIFF1YXJhbnRpbmU8L2gxPgogICAgPGRpdiBjbGFzcz0ibXV0ZWQiPnYwMDEgwrcgZ2VuZXJhdGVkIDkvMjAvMjAyNiAyOjM0OjI4IHBtIEVUPC9kaXY+CgogICAgPD9waHAgaWYgKCRtZXNzYWdlICE9PSAnJyk6ID8+CiAgICAgICAgPGRpdiBjbGFzcz0ibm90aWNlIDw/cGhwIGVjaG8gaCgkbWVzc2FnZUNsYXNzKTsgPz4iPjw/cGhwIGVjaG8gaCgkbWVzc2FnZSk7ID8+PC9kaXY+CiAgICA8P3BocCBlbmRpZjsgPz4KCiAgICA8ZGl2IGNsYXNzPSJjYXJkIj4KICAgICAgICA8aDI+RmlsZXM8L2gyPgogICAgICAgIDx0YWJsZT4KICAgICAgICAgICAgPHRyPjx0aD5GaWxlPC90aD48dGg+Um9vdDwvdGg+PHRoPlF1YXJhbnRpbmU8L3RoPjwvdHI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD48Y29kZT5jb21wb3Nlci5qc29uPC9jb2RlPjwvdGQ+CiAgICAgICAgICAgICAgICA8dGQgY2xhc3M9Ijw/cGhwIGVjaG8gJHN0YXRlSnNvblsnc291cmNlJ10gPyAnZ29vZCcgOiAnbXV0ZWQnOyA/PiI+PD9waHAgZWNobyAkc3RhdGVKc29uWydzb3VyY2UnXSA/ICdQUkVTRU5UJyA6ICdBQlNFTlQnOyA/PjwvdGQ+CiAgICAgICAgICAgICAgICA8dGQgY2xhc3M9Ijw/cGhwIGVjaG8gJHN0YXRlSnNvblsnZGVzdCddID8gJ2dvb2QnIDogJ211dGVkJzsgPz4iPjw/cGhwIGVjaG8gJHN0YXRlSnNvblsnZGVzdCddID8gJ1BSRVNFTlQnIDogJ0FCU0VOVCc7ID8+PC90ZD4KICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgPHRkPjxjb2RlPmNvbXBvc2VyLmxvY2s8L2NvZGU+PC90ZD4KICAgICAgICAgICAgICAgIDx0ZCBjbGFzcz0iPD9waHAgZWNobyAkc3RhdGVMb2NrWydzb3VyY2UnXSA/ICdnb29kJyA6ICdtdXRlZCc7ID8+Ij48P3BocCBlY2hvICRzdGF0ZUxvY2tbJ3NvdXJjZSddID8gJ1BSRVNFTlQnIDogJ0FCU0VOVCc7ID8+PC90ZD4KICAgICAgICAgICAgICAgIDx0ZCBjbGFzcz0iPD9waHAgZWNobyAkc3RhdGVMb2NrWydkZXN0J10gPyAnZ29vZCcgOiAnbXV0ZWQnOyA/PiI+PD9waHAgZWNobyAkc3RhdGVMb2NrWydkZXN0J10gPyAnUFJFU0VOVCcgOiAnQUJTRU5UJzsgPz48L3RkPgogICAgICAgICAgICA8L3RyPgogICAgICAgIDwvdGFibGU+CgogICAgICAgIDxwPgogICAgICAgICAgICBRdWFyYW50aW5lIGZvbGRlcjoKICAgICAgICAgICAgPGNvZGU+PD9waHAgZWNobyBoKCRxdWFyYW50aW5lRGlyKTsgPz48L2NvZGU+CiAgICAgICAgPC9wPgoKICAgICAgICA8ZGl2IGNsYXNzPSJidXR0b25zIj4KICAgICAgICAgICAgPGZvcm0gbWV0aG9kPSJwb3N0IiBvbnN1Ym1pdD0icmV0dXJuIGNvbmZpcm0oJ01vdmUgY29tcG9zZXIuanNvbiBhbmQgY29tcG9zZXIubG9jayBpbnRvIHF1YXJhbnRpbmU/IE5vdGhpbmcgd2lsbCBiZSBkZWxldGVkLicpOyI+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJhY3Rpb24iIHZhbHVlPSJxdWFyYW50aW5lIj4KICAgICAgICAgICAgICAgIDxidXR0b24gY2xhc3M9InF1YXJhbnRpbmUiIHR5cGU9InN1Ym1pdCIgPD9waHAgZWNobyAkY2FuUXVhcmFudGluZSA/ICcnIDogJ2Rpc2FibGVkJzsgPz4+UXVhcmFudGluZSBDb21wb3NlciBGaWxlczwvYnV0dG9uPgogICAgICAgICAgICA8L2Zvcm0+CgogICAgICAgICAgICA8Zm9ybSBtZXRob2Q9InBvc3QiIG9uc3VibWl0PSJyZXR1cm4gY29uZmlybSgnUmVzdG9yZSBjb21wb3Nlci5qc29uIGFuZCBjb21wb3Nlci5sb2NrIGZyb20gcXVhcmFudGluZT8nKTsiPgogICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iYWN0aW9uIiB2YWx1ZT0icmVzdG9yZSI+CiAgICAgICAgICAgICAgICA8YnV0dG9uIGNsYXNzPSJyZXN0b3JlIiB0eXBlPSJzdWJtaXQiIDw/cGhwIGVjaG8gJGNhblJlc3RvcmUgPyAnJyA6ICdkaXNhYmxlZCc7ID8+PlJlc3RvcmUgQ29tcG9zZXIgRmlsZXM8L2J1dHRvbj4KICAgICAgICAgICAgPC9mb3JtPgogICAgICAgIDwvZGl2PgogICAgPC9kaXY+CgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPk5vdCB0b3VjaGVkPC9oMj4KICAgICAgICA8cCBjbGFzcz0ibXV0ZWQiPgogICAgICAgICAgICBSb290IDxjb2RlPi92ZW5kb3I8L2NvZGU+LCBXb3JkUHJlc3MgdmVuZG9yIGZvbGRlcnMsIFBIUCBmaWxlcywgSlMgZmlsZXMsIGRhdGFiYXNlLCBzY2hlZHVsZXIsIGNyb24sIGFuZCBjb25maWd1cmF0aW9uIGFyZSB1bnRvdWNoZWQuCiAgICAgICAgPC9wPgogICAgPC9kaXY+CjwvZGl2Pgo8L2JvZHk+CjwvaHRtbD4K';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    }

    $output = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');

    if ($output === null) {
        return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }

    $output = trim((string)$output);

    return [
        'ok'=>stripos($output,'No syntax errors detected') !== false,
        'output'=>$output
    ];
}

$realPath = __DIR__ . '/' . REAL_FILE_NAME;
$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';
$lint = null;

if ($action === 'stage') {
    $payload = base64_decode(REAL_FILE_B64, true);

    if ($payload === false) {
        $message = 'FAIL — embedded installer payload could not be decoded.';
        $messageClass = 'bad';
    } elseif (hash('sha256', $payload) !== REAL_FILE_SHA256) {
        $message = 'FAIL — embedded installer payload hash did not match.';
        $messageClass = 'bad';
    } else {
        if (is_file($realPath)) {
            $existing = (string)@file_get_contents($realPath);

            if (hash('sha256', $existing) !== REAL_FILE_SHA256) {
                $message = 'FAIL — a different file already exists with the installer filename. Nothing was overwritten.';
                $messageClass = 'bad';
            }
        }

        if ($message === '' && !is_file($realPath)) {
            if (@file_put_contents($realPath, $payload, LOCK_EX) === false) {
                $message = 'FAIL — could not stage the real installer.';
                $messageClass = 'bad';
            } else {
                @chmod($realPath, 0644);
            }
        }

        if ($message === '') {
            $lint = lint_php_file($realPath);

            if (!empty($lint['ok'])) {
                $message = 'PASS — real installer staged, hash-matched, and PHP lint passed.';
                $messageClass = 'good';
            } else {
                $message = 'FAIL — real installer was staged but did not pass PHP lint.';
                $messageClass = 'bad';
            }
        }
    }
}

if ($lint === null && is_file($realPath)) {
    $existing = (string)@file_get_contents($realPath);
    if (hash('sha256', $existing) === REAL_FILE_SHA256) {
        $lint = lint_php_file($realPath);
    }
}

$ready = is_file($realPath)
    && $lint !== null
    && !empty($lint['ok'])
    && hash_file('sha256', $realPath) === REAL_FILE_SHA256;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Composer Files Quarantine Lint Gate</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:950px;margin:0 auto}
h1{color:var(--gold);margin:0 0 6px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.pass{color:var(--good);font-weight:800}.fail{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-all}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:white;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.stage{background:#248c4b}.open{background:#276fca}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:10px;padding:12px}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Composer Files Quarantine Lint Gate</h1>
    <div class="muted">Generated 9/20/2026 2:34:28 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Real installer</h2>
        <p><code><?php echo h(REAL_FILE_NAME); ?></code></p>
    </div>

    <div class="card">
        <h2>Lint status</h2>
        <?php if ($ready): ?>
            <p class="pass">PASS — installer is staged, hash-matched, and lint-clean.</p>
        <?php elseif ($lint !== null): ?>
            <p class="fail">NOT READY — review lint result.</p>
        <?php else: ?>
            <p class="muted">Not staged yet.</p>
        <?php endif; ?>

        <?php if ($lint !== null): ?>
            <pre><?php echo h((string)$lint['output']); ?></pre>
        <?php endif; ?>

        <div class="buttons">
            <form method="post">
                <input type="hidden" name="action" value="stage">
                <button class="stage" type="submit">Stage + Lint Real Installer</button>
            </form>

            <?php if ($ready): ?>
                <a class="btn open" href="<?php echo h(REAL_FILE_NAME); ?>">Open Real Installer</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
